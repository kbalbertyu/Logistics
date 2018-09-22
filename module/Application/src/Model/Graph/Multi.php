<?php
namespace Application\Model\Graph;

class Multi extends Graph {
    protected $category;
    
    public function toChart() {
        return [
            'chart' => [
                'caption' => $this->caption,
                'numberPrefix' => $this->numberPrefix,
                'theme' => 'fint',
                'captionFontSize' => '14',
                'subcaptionFontSize' => '14',
                'subcaptionFontBold' => '0',
                'paletteColors' => '#06f9f9,#f91106,#f98106,#58f906,#0619f9,#df06f9,#008ee4,#9b59b6,#6baa01,#e44a00',
                'bgcolor' => '#ffffff',
                'showBorder' => '1',
                'showShadow' => '0',
                'showCanvasBorder' => '1',
                'usePlotGradientColor' => '1',
                'legendBorderAlpha' => '0',
                'legendShadow' => '0',
                'showAxisLines' => '1',
                'showAlternateHGridColor' => '1',
                'divlineThickness' => '1',
                'divLineIsDashed' => '1',
                'divLineDashLen' => '1',
                'divLineGapLen' => '1',
                'xAxisName' => $this->xName,
                'showValues' => '1'
            ],
            'categories' => [
                ['category' => $this->category]
            ],
            'dataset' => $this->dataSet
        ];
    }
}