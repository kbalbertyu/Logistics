<?php
namespace Application\Model\Graph;

class Single extends Graph {
    
    public function toChart() {
        return [
            'chart' => [
                'caption' => $this->caption,
                'xAxisName' => $this->xName,
                'yAxisName' => $this->yName,
                'theme' => 'fint',
                'showBorder' => '1',
            ],
            'data' => $this->dataSet
        ];
    }
}