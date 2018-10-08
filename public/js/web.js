$(function(){
	$('.nav-tabs a.nav-link, a.dropdown-link, .set-value').click(function(e){
		e.preventDefault();
		prepareSelection(this);
		var name = $(this).attr('data-name');
		var value = $(this).attr('data-value');
		$('input[name=' + name + ']').val(value);
		$('#search').submit();
	});
	
	function prepareSelection(o) {
		var groupBy = $('input[name=group_by]');
		if (groupBy.length) {
			if ($(o).attr('data-name') == 'currency' && 
				$(o).attr('data-value').toLowerCase() == 'original' && 
				groupBy.val().toLowerCase() == 'account') {
				groupBy.val('Account-Country');
			} else if ($(o).attr('data-name') == 'group_by' &&
				$(o).attr('data-value').toLowerCase() == 'account') {
				var currency = $('input[name=currency]');
				if (currency.val().toLowerCase() == 'original') {
					currency.val('USD');
				}
			}
		}
	}
	
	$.fn.dataTableExt.oSort['numeric-comma-asc']  = function(a,b) {
		var x = (a == "") ? 0 : a;
		var y = (b == "") ? 0 : b;
		x = parseFloat( x );
		y = parseFloat( y );
		return ((x < y) ? -1 : ((x > y) ?  1 : 0));
	};

	$.fn.dataTableExt.oSort['numeric-comma-desc'] = function(a,b) {
		var x = (a == "") ? 0 : a;
		var y = (b == "") ? 0 : b;
		x = parseFloat( x );
		y = parseFloat( y );
		return ((x < y) ?  1 : ((x > y) ? -1 : 0));
	};

	$('#summary-table').dataTable({
		"aoColumns": [
			null,
			null,
			null,
			{ "sType": "numeric-comma" },
			{ "sType": "numeric-comma" },
			{ "sType": "numeric-comma" },
			{ "sType": "numeric-comma" },
			{ "sType": "numeric-comma" },
			{ "sType": "numeric-comma" },
			{ "sType": "numeric-comma" },
			{ "sType": "numeric-comma" },
            { "sType": "numeric-comma" },
            { "sType": "numeric-comma" }
		],
		paging: false,
		searching: false
	});
	$('#statistic-table').dataTable({
		"aoColumns": [
			null,
			{ "sType": "numeric-comma" },
			{ "sType": "numeric-comma" },
			{ "sType": "numeric-comma" },
			{ "sType": "numeric-comma" },
			{ "sType": "numeric-comma" },
			{ "sType": "numeric-comma" },
			{ "sType": "numeric-comma" },
			{ "sType": "numeric-comma" }
		],
		paging: false,
		searching: false
	});
	$('#account-table, #health-table').dataTable({
		paging: false
	});
    $('#package-table-in').dataTable({
        "aoColumns": [
            { "sType": "numeric-comma" },
            null,
            null,
            null,
            { "sType": "numeric-comma" },
            { "sType": "numeric-comma" },
            null,
            { "sType": "numeric-comma" },
            null,
            null,
            null,
        ],
    });
    $('#package-table-out').dataTable({
        "aoColumns": [
            { "sType": "numeric-comma" },
            null,
            null,
            null,
            null,
            { "sType": "numeric-comma" },
            { "sType": "numeric-comma" },
            null,
            { "sType": "numeric-comma" },
            null,
            null,
            null,
        ],
    });
    $('#product-table').dataTable({
        "aoColumns": [
            { "sType": "numeric-comma" },
            null,
            null,
            null,
            { "sType": "numeric-comma" },
            { "sType": "numeric-comma" },
            { "sType": "numeric-comma" },
            { "sType": "numeric-comma" },
            { "sType": "numeric-comma" },
            null
        ],
    });
    $('#team-table').dataTable({
        "aoColumns": [
            { "sType": "numeric-comma" },
            null,
            { "sType": "numeric-comma" },
            { "sType": "numeric-comma" },
            null
        ],
    });
    $('#charge-table').dataTable({
        "aoColumns": [
            { "sType": "numeric-comma" },
            null,
            null,
            { "sType": "numeric-comma" },
            null
        ],
    });
	
	function highLightCol(index, oTable) {
		$('td.yHighlight', oTable).removeClass('yHighlight');
		$('tbody > tr', oTable).each(function() {
			$('td:eq(' + index + ')', this).addClass('yHighlight');
		});
	}
	function highLightRow(index, oTable) {
		$('tr.xHighlight', oTable).removeClass('xHighlight');
		$('tbody > tr:eq(' + index + ')', oTable).addClass('xHighlight');
	}
	$('table.display > tbody > tr > td').click(function() {
		var oTr = $(this).parent();
		var oTable = oTr.parent().parent();
		highLightRow(oTr.index(), oTable);
		highLightCol($(this).index(), oTable);
	});
	$("[data-toggle='tooltip']").tooltip();


    function showBrand(brandId) {
        $.getJSON(BASE_PATH + 'inventory/get-brand/' + brandId, function(data) {
            $('#brand').val(data.name);
        });
    }
	/** Auto Complete **/
	if ($('#brand').length) {
        var BRAND_CACHE = {};
        $('#brand').autocomplete({
            minLength: 2,
            source: function (request, response) {
                var term = request.term;
                if (term in BRAND_CACHE) {
                    response(BRAND_CACHE[term]);
                    return;
                }

                $.getJSON(BASE_PATH + 'inventory/get-brand-names', request, function (data) {
                    BRAND_CACHE[term] = data;
                    response(data);
                });
            }
        });
    }

    if ($('#itemName').length) {
        var ITEM_CACHE = {};
        $('#itemName').autocomplete({
            minLength: 2,
            source: function (request, response) {
                var term = request.term;
                if (term in ITEM_CACHE) {
                    response(ITEM_CACHE[term]);
                    return;
                }
                $.getJSON(BASE_PATH + 'inventory/get-item-names', request, function (data) {
                    ITEM_CACHE[term] = data;
                    response(data);
                });
            },
            focus: function (event, ui) {
                $('#itemName').val(ui.item.label);
                return false;
            },
            select: function (event, ui) {
                $('#itemName').val(ui.item.label);
                showBrand(ui.item.brandId);
                return false;
            }
        });
    }
    function showServiceFeeTotal() {
	    var total = 0;
        $('[id$=-preview]').each(function() {
            if ($(this).attr('id') == 'total-preview') {
                return;
            }
            var fee = parseFloat($(this).text());
            total += isNaN(fee) ? 0 : fee;
        });
        $('#serviceFee').val(total);
    }
    function parsePrice(label) {
        var price = label.replace(/[^\.0-9]/ig,"");
        return parseFloat(price);
    }
    function getServiceFee(field, unitPrice) {
	    var count;
        if (field == 'needBoxChange') {
            count = $('#caseQty').val();
        } else {
            count = $('#qty').val();
        }
        count = parseInt(count);
        count = isNaN(count) ? 0 : count;
        return count * unitPrice;
    }
    function previewServiceFee(obj) {
        var field = obj.attr('name');
        var objPreview = $('#' + field + '-preview');
        if (obj.is(':checked')) {
            var label = obj.siblings('[for=' + field + ']').eq(0).text();
            var unitPrice = parsePrice(label);
            var fee = getServiceFee(field, unitPrice);
            objPreview.text(fee);
        } else {
            objPreview.text('0');
        }
        showServiceFeeTotal();
    }
    $('#requirements-table input[type=checkbox]').change(function () {
        previewServiceFee($(this));
    });
	$('#requirements-table input[type=checkbox]').each(function () {
        previewServiceFee($(this));
    });
    showServiceFeeTotal();
})
 