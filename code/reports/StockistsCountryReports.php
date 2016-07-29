<?php

/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @sub-package: reports
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class StockistsCountryReports_WithoutCountry extends SS_Report {

    /**
     * The class of object being managed by this report.
     * Set by overriding in your subclass.
     */
    protected $dataClass = 'StockistCountryPage';

    /**
     *
     * @return String
     */
    function title() {
        return "STOCKISTS: stockist countries without a country (".$this->sourceRecords()->count().")";
    }

    /**
     * not sure if this is used in SS3
     * @return String
     */
    function group() {
        return "Stockists";
    }

    /**
     *
     * @return INT - for sorting reports
     */
    function sort() {
        return 9000;
    }

    /**
     * working out the items
     * @return DataList
     */
    function sourceRecords($params = null) {
        return StockistCountryPage::get()->where("Country = '' OR Country IS NULL");
    }

    /**
     * @return Array
     */
    function columns() {
        return array(
            "Title" => array(
                "title" => "FullName",
                "link" => true
            )
        );
    }

    /**
     *
     * @return FieldList
     */
    public function getParameterFields() {
        return new FieldList();
    }
}
