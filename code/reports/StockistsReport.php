<?php

/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @sub-package: reports
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class StockistsReport_WithoutAPicture extends SS_Report {

    /**
     * The class of object being managed by this report.
     * Set by overriding in your subclass.
     */
    protected $dataClass = 'StockistPage';

    /**
     *
     * @return String
     */
    function title() {
        return "STOCKISTS: without a picture (".$this->sourceRecords()->count().")";
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
        return StockistPage::get()->filter(array("ImageID" => 0));
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

class StockistsReport_WithoutALogo extends SS_Report {

    /**
     * The class of object being managed by this report.
     * Set by overriding in your subclass.
     */
    protected $dataClass = 'StockistPage';

    /**
     *
     * @return String
     */
    function title() {
        return "STOCKISTS: without a logo (".$this->sourceRecords()->count().")";
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
        return StockistPage::get()->filter(array("LogoID" => 0));
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

class StockistsReport_WithoutAnAddress extends SS_Report {

    /**
     * The class of object being managed by this report.
     * Set by overriding in your subclass.
     */
    protected $dataClass = 'StockistPage';

    /**
     *
     * @return String
     */
    function title() {
        return "STOCKISTS: without an address (".$this->sourceRecords()->count().")";
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
        $stage = '';
        if (Versioned::current_stage() == 'Live') {
            $stage = '_Live';
        }
        return StockistPage::get()->where("GoogleMapLocationsObject.ID IS NULL OR HasGeoInfo = 0")
            ->leftJoin("GoogleMapLocationsObject", "GoogleMapLocationsObject.ParentID = StockistPage".$stage.".ID");
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


class StockistsReport_OnlineStockists extends SS_Report {

    /**
     * The class of object being managed by this report.
     * Set by overriding in your subclass.
     */
    protected $dataClass = 'StockistPage';

    /**
     *
     * @return String
     */
    function title() {
        return "STOCKISTS: has online store (".$this->sourceRecords()->count().")";
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
        return StockistPage::get()->filter(array("HasWebStore" => 1));
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
