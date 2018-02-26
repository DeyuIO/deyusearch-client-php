<?php 

namespace DeyuSearch\Frameworks\Scout;

trait DeyuSearchable
{

    public $highlights = [];

    public function getSearchSettings()
    {
        return property_exists($this, 'searchSettings') ? $this->searchSettings : [];
    }
}