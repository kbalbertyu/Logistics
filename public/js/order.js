var FAIL = 'Fail';
var SHEET_UNIT_NUM = 50;
var MAX_RETRY_TIMES = 5;
var RETRY_WAIT_TIME = 5000;
var ERROR_SPREAD_IDS = ['1EnM5Jh1kvh4xO8AEqx-HhNKFpxplgC18y-ZMiOewn-I', '1JdG5MKy-lUXFCtHVr3YulD1Db2ZlC01X-IFFy7EC17M'];
var MAX_ORDERS_PER_IMPORT = 100;

function Order(spreads) {
    this.spreads = spreads;
    this.pause = false;
    this.index = 0;
    this.spread;
    this.spreadLength = spreads.length;
    this.sheetNames = [];

    this.fetchOrderRetry = 0;
    this.fetchSheetNameRetry = 0;
    this.orders = {};
    this.currentSheetNames = [];
    this.header = [];
    this.importingOrderSet;

    this.setPause = function (flag) {
        this.pause = flag;
        return this;
    }

    this.showTotal = function () {
        $('#total').text(this.spreadLength);
    }

    this.showProgress = function () {
        var nowPrecent = (this.index + 1) * 100 / this.spreadLength;
        $('.progress-bar').attr('aria-valuenow', nowPrecent).css('width', nowPrecent + '%');
    }

    this.showMessage = function (message) {
        $('#message').prepend('<li>' + message + '</li>');
    };

    this.showError = function (message) {
        $('#error').prepend('<li>' + message + '</li>');
    }

    this.hasResultError = function (result) {
        return result.hasOwnProperty('status') && result.status == FAIL;
    }

    this.execute = function () {
        this.showProgress();
        if (this.pause) {
            this.showMessage('The process is pause!');
            alert('Pause!');
            return;
        }
        if (this.index >= this.spreadLength) {
            this.showMessage('The process is done!');
            alert('Done!');
            return;
        }
        this.spread = this.spreads[this.index];
        if (ERROR_SPREAD_IDS.indexOf(this.spread.id) != -1) {
            this.showMessage('Error spread is skipped: ' + this.spread.title + ' -> ' + this.spread.id);
            this.index++;
            this.execute();
            return;
        }
        this.showMessage('Fetching sheet names: ' + this.spread.title);
        this.fetchSheetNameRetry = 0;
        this.fetchSheetNames(function (o) {
            if (o.sheetNames == null || o.sheetNames.length == 0) {
                o.showError('No sheet names fetched: ' + o.spread.title);
                o.index++;
                o.execute();
                return;
            }
            o.showMessage(o.sheetNames.length + ' sheet names fetched: ' + o.spread.title + ', starting reading orders.');
            o.handleSheetNames();
        });
    }

    this.handleSheetNames = function () {
        if (this.sheetNames == null || this.sheetNames.length == 0) {
            this.showMessage('Spread orders imported: ' + this.spread.title);
            this.index++;
            this.execute();
            return;
        }
        var sheetNames;
        if (this.sheetNames.length >= SHEET_UNIT_NUM * 2) {
            sheetNames = this.sheetNames.slice(0, SHEET_UNIT_NUM);
            this.sheetNames = this.sheetNames.slice(SHEET_UNIT_NUM);
        } else {
            sheetNames = this.sheetNames;
            this.sheetNames = [];
        }
        this.currentSheetNames = sheetNames;
        this.fetchOrderRetry = 0;
        this.fetchOrders(sheetNames, function (o) {
            o.showMessage('Orders fetched, start importing: ' + o.spread.title + ' ->' + sheetNames.join(', '));
            o.importOrders();
        });
    }

    this.fetchOrders = function (sheetNames, callback) {
        var params = {
            func: 'getOrders',
            params: JSON.stringify({
                spreadId: this.spread.id,
                sheetNames: sheetNames
            })
        };

        var o = this;
        $.get(FETCH_API, params).then(
            function (orders) {
                if (o.hasResultError(orders)) {
                    o.showError('Fetch orders error: ' + o.spread.title + '<br />Message: ' + orders.message);
                    o.setPause(true);
                    o.execute();
                } else if ($.isEmptyObject(orders)) {
                    o.showMessage('No order found in: ' + o.spread.title + ', ' + sheetNames.join(', '));
                    o.handleSheetNames();
                } else {
                    o.orders = orders;
                    callback(o);
                }
            }, function () {
                if (o.fetchOrderRetry++ < MAX_RETRY_TIMES) {
                    o.showMessage("Fetch orders failed: " + o.spread.title + ', retry in ' + (RETRY_WAIT_TIME / 1000) + ' seconds.');
                    setTimeout(function () {
                        o.showMessage('Retry fetching orders...');
                        o.fetchOrders(sheetNames, callback);
                    }, RETRY_WAIT_TIME);
                    return;
                }
                o.showError("Fetch orders failed: " + o.spread.title);
                o.setPause(true);
                o.execute();
            }
        );
    }

    this.isHeader = function (row) {
        return row[0].toString().toLowerCase() == 'status' &&
            row[1].toString().toLowerCase() == 'order-id' &&
            row[2].toString().toLowerCase() == 'recipient-name'
    }

    this.getOrdersData = function () {
        var orders = {}, count = 0, sheetNames = [];
        for (var sheetName in this.orders) {
            var needed = MAX_ORDERS_PER_IMPORT - count;
            if (needed <= 0) {
                break;
            }

            var orderList = this.orders[sheetName], toAdd;
            if (orderList.length - needed > 20) {
                toAdd = orderList.slice(0, needed);
                this.orders[sheetName] = orderList.slice(needed);
            } else {
                toAdd = orderList;
                sheetNames.push(sheetName);
            }
            if (!this.isHeader(toAdd[0]) && this.header.length != 0) {
                toAdd.unshift(this.header);
            }
            orders[sheetName] = toAdd;
            count += toAdd.length;
        }
        for (var i = 0; i < sheetNames.length; i++) {
            delete this.orders[sheetNames[i]];
        }
        return {
            spreadId: this.spread.id,
            orderSet: orders,
            count: count
        };
    }

    this.importOrders = function () {
        if ($.isEmptyObject(this.orders)) {
            this.showMessage('Ordes are imported: ' + this.spread.title + ' -> ' + this.currentSheetNames.join(', '));
            this.handleSheetNames();
            return;
        }
        var data = this.getOrdersData();
        this.importingOrderSet = data;
        var sheetNames = Object.keys(data.orderSet).join(', ');
        this.showMessage('Importing data from sheet names: ' + this.spread.title + ' -> ' + sheetNames + ': ' + data.count);
        var o = this;
        $.post(IMPORT_API, data).then(function (result) {
            if (result.code == FAIL) {
                o.showError("Import orders failed: " + o.spread.title + ' -> ' + sheetNames + ', Error: ' + result.message);
                o.setPause(true);
                o.execute();
            } else {
                result = result.result;
                var message = 'Orders imported: ' + o.spread.title + ' ->' + sheetNames;
                message += '<br />Success=' + result.success + ', Skip=' + result.skip + ', Fail=' + result.fail;
                if (result.invalid.length > 0) {
                    message += '<br />Invalid header: <br />' + result.invalid.join('<br />');
                }
                o.showMessage(message);
                if (result.success == 0 && result.skip == 0 && result.fail == 0) {
                    o.setPause(true);
                    o.execute();
                    return;
                }
                o.header = result.header;
                o.importOrders();
            }
        }, function (result) {
            o.showError("Import orders failed: " + o.spread.title + ', Error: ' + result.message);
            o.setPause(true);
            o.execute();
        });
    }

    this.fetchSheetNames = function (callback) {
        var params = {
            func: 'getSheetNames',
            params: JSON.stringify({
                spreadId: this.spread.id
            })
        };

        var o = this;
        $.get(FETCH_API, params).then(
            function (sheetNames) {
                if (o.hasResultError(sheetNames)) {
                    o.showError('Fetch sheet names error: ' + o.spread.title + '<br />Message: ' + sheetNames.message);
                    o.setPause(true);
                    o.execute();
                } else {
                    o.sheetNames = sheetNames;
                    callback(o);
                }
            }, function () {
                if (o.fetchSheetNameRetry++ < MAX_RETRY_TIMES) {
                    o.showMessage('Fetch sheet names failed: ' + o.spread.title + ', retry in ' + (RETRY_WAIT_TIME / 1000) + ' seconds.');
                    setTimeout(function () {
                        o.showMessage('Retry fetching sheet names...');
                        o.fetchSheetNames(callback);
                    }, RETRY_WAIT_TIME);
                    return;
                }
                o.showError('Fetch sheet names failed: ' + o.spread.title);
                o.setPause(true);
                o.execute();
            }
        );
    }
}

var order = new Order(spreads);
order.showTotal();
$(function () {
    $('#start').click(function (e) {
        e.preventDefault();
        order.setPause(false).execute();
    });

    $('#pause').click(function (e) {
        e.preventDefault();
        order.setPause(true);
    });
})