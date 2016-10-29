<?php

class StockistSearchPage extends Page
{

    /**
     * @inherited
     */
    private static $icon = 'mysite/images/treeicons/StockistSearchPage';

    /**
     * @inherited
     */
    private static $db = array(
        "DefaultZoom" => "Int"
    );

    /**
     * @inherited
     */
    private static $default_child = 'StockistCountryPage';

    /**
     * @inherited
     */
    private static $allowed_children = array('StockistCountryPage');

    /**
     * @inherited
     */
    private static $defaults = array('DefaultZoom' => 0);

    /**
     * @inherited
     */
    private static $can_be_root = true;

    /**
     * @inherited
     */
    private static $description = 'Stockist Search Page - Main page for Stockists';

    /**
     * Standard SS variable.
     */
    private static $singular_name = "Stockist Search Page";
    public function i18n_singular_name()
    {
        return "Stockist Search Page";
    }

    /**
     * Standard SS variable.
     */
    private static $plural_name = "Stockist Search Pages";
    public function i18n_plural_name()
    {
        return "Stockist Search Pages";
    }

    /**
     * @inherited
     */
   public function getCMSFields()
   {
       $fields = parent::getCMSFields();
       $fields->removeByName("Map");
       $fields->addFieldToTab(
            "Root.SearchHistory", new LiteralField("SearchHistoryLink", "<a href=\"".$this->Link("showsearches")."\">What did people search for?</a>")
        );
       $fields->addFieldToTab('Root.Map', $defaultZoomField = new NumericField('DefaultZoom'));
       $defaultZoomField->setRightTitle('Set between 1 and 20.  One is the whole world and twenty is highest zoom level for map. Leave at zero for auto-zoom.');
       return $fields;
   }

    /**
     *
     * can only create one
     */
    public function canCreate($member = null)
    {
        return StockistSearchPage::get()
            ->filter(array("ClassName" => "StockistSearchPage"))
            ->count() ? false : true;
    }


    /**
     * returns a list of continents
     * @return DataList
     */
    public function AllContintents()
    {
        if ($root = $this->StockistSearchPage()) {
            return StockistCountryPage::get()->filter(array("ParentID" => $root->ID))->sort(array("Title" => "ASC"));
        }
    }

    /**
     * this is only provided on a country level
     * @return DataList | Null
     */
    public function AllPhysicalStockists()
    {
        return null;
    }

    /**
     * this is only provided on a country level
     * @return DataList | Null
     */
    public function AllOnlineStockists()
    {
        return null;
    }

    /**
     * @return Boolean
     */
    public function HasPhysicalStockistsANDOnlineStockists()
    {
        $a = $this->AllPhysicalStockists();
        $b = $this->AllOnlineStockists();
        if ($a && $b) {
            if ($a->count() && $b->count()) {
                return true;
            }
        }
        return false;
    }
}

class StockistSearchPage_Controller extends Page_Controller
{

    /**
     * @inherited
     */
    private static $allowed_actions = array(
        "showsearches" => "ADMIN",
        "updatemap" => "ADMIN",
        "showtype" => true,
        "showcountries" => true,
        "listofstockists" => true,
        "listofstockistscsv" => true,
        'addresssearch' => true,
        'addresssearchmap' => true,
        'SearchByAddressForm' => true,
    );

    public function init()
    {
        parent::init();
        $this->myCurrentCountryCode = EcommerceCountry::get_country();
        $this->HasGeoInfo = true;
    }

    public function index()
    {
        $this->addMap(
            $action = "showpointbyid",
            $title = $this->Title.' - '.$this->MyStockistCountryTitle(),
            $lng = 0,
            $lat = 0,
            implode(',', $this->locationsForCurrentCountry()->column("ID"))
        );
        return array();
    }

    public function MyAddAddressFinderForm()
    {
        return $this->AddressFinderForm(array("StockistPage"));
    }

    /**
     * returns all the locations (GoogleMapLocationsObject) relevant
     * to the current user's country for display on the map.
     * We get the country from:
     * ```php
     *     $countryCode = EcommerceCountry::get_country();
     * ```
     *
     * @return DataList of GoogleMapLocationsObject
     */
    protected function locationsForCurrentCountry()
    {
        $objects = null;
        if ($this->myCurrentCountryCode) {
            $objects = GoogleMapLocationsObject::get()->filter(array("CountryNameCode" => $this->myCurrentCountryCode));
            if ($objects->count()) {
                return $objects;
            }
        }
        return GoogleMapLocationsObject::get();
    }

    public function AlphaList()
    {
        if ($objects = $this->locationsForCurrentCountry()) {
            $parents = $objects->column('ParentID');
            if (count($parents)) {
                $pages = StockistPage::get()->filter(
                    array(
                        "ID" => $parents
                    )
                );
                if ($pages->count() < 25) {
                    return $pages;
                }
            }
        }
    }

    /**
     * @return DataList
     */
    public function Countries()
    {
        $objects = StockistCountryPage::get()->filter(array("ParentID" => $this->ID))->sort(array("Title" => "ASC"));
        if ($objects->count() == 0) {
            return StockistCountryPage::get()->filter(array("ID" => $this->ID));
        }
        return $objects;
    }

    /**
     * for template
     * @return Boolean
     */
    public function IsSearchPage()
    {
        return true;
    }

    public function IsStockistPage()
    {
        return true;
    }

    public function showsearches()
    {
        if (Permission::check('ADMIN')) {
            $sql = "
                SELECT SearchedFor
                FROM \"GoogleMapSearchRecord\"
                ORDER BY Created DESC
                LIMIT 10000
            ";
            $rows = DB::query($sql);
            if ($rows) {
                $this->Content .= "<h2>previous searches</h2><ul>";
                foreach ($rows as $row) {
                    $this->Content .= "<li>".$row["SearchedFor"]."</li>";
                }
                $this->Content .= "</ul>";
            }
            return array();
        }
        Security::permissionFailure($this, "You need to be logged in as administrator to see this map");
    }

    /**
     *
     * @param HTTPRequest
     */
    public function showtype($request)
    {
        $type = $request->param("ID");
        return $this->redirect($this->Link()."#".$this->Link("showtype/$type/"));
    }

    /**
     * we use this specfically so that we can change the country for
     * a page.
     * @return String
     */
    public function MyStockistCountryTitle()
    {
        if ($this->myCurrentCountryCode) {
            return EcommerceCountry::find_title($this->myCurrentCountryCode);
        } else {
            return $this->MyCountryTitle();
        }
    }


    ####################################
    # CSV
    ####################################

    /**
     *
     * download CSV action
     */
    public function listofstockistscsv()
    {
        $html = $this->createListOfStockists();
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8");

        /*** a new dom object ***/
        $dom = new domDocument;

        /*** load the html into the object ***/
        $dom->loadHTML($html);

        /*** discard white space ***/
        $dom->preserveWhiteSpace = false;

        /*** the table by its tag name ***/
        $htmlTable = $dom->getElementsByTagName('table');
        $htmlRows = $htmlTable->item(0)->getElementsByTagName('tr');
        $rowArray = array();
        foreach ($htmlRows as $htmlRow) {
            $headerCells = array();
            $htmlHeaders = $htmlRow->getElementsByTagName('th');
            foreach ($htmlHeaders as $htmlHeader) {
                $headerCells [] = $htmlHeader->nodeValue;
            }
            $htmlCells = $htmlRow->getElementsByTagName('td');
            $rowCells = array();
            foreach ($htmlCells as $htmlCell) {
                $rowCells [] = $htmlCell->nodeValue;
            }
            $rowArray[] = '"'.implode('", "', array_merge($headerCells, $rowCells)).'"';
        }
        $csv = implode("\r\n", $rowArray);
        $filename_prefix = 'stockists';
        $filename = $filename_prefix."_".date("Y-m-d_H-i", time());

        //Generate the CSV file header
        header("Content-type: application/vnd.ms-excel");
        header("Content-Encoding: UTF-8");
        header("Content-type: text/csv; charset=UTF-8");
        header("Content-disposition: csv" . date("Y-m-d") . ".csv");
        header("Content-disposition: filename=".$filename.".csv");
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        //Print the contents of out to the generated file.
        print $csv;
    }


    /**
     *
     * create HTML row for table of stockists
     */
    private function getChildrenAsHTMLRows($parent)
    {
        $childGroups = DataObject::get("SiteTree", "ParentID = ".$parent->ID);
        $html = "";
        if ($childGroups) {
            foreach ($childGroups as $childGroup) {
                $html .= $this->getChildrenAsHTMLRows($childGroup);
            }
        }
        $childStockists = DataObject::get("StockistPage", "ParentID = ".$parent->ID);
        if ($childStockists) {
            foreach ($childStockists as $stockist) {
                $stockistParent = $stockist;
                $parentNames = array();
                $stockistParent = DataObject::get_by_id("SiteTree", $stockistParent->ParentID);
                while ($stockistParent && ($stockistParent->ParentID)) {
                    $parentNames[] = $stockistParent->Title;
                    $stockistParent = DataObject::get_by_id("SiteTree", $stockistParent->ParentID);
                }
                $html .= "<tr>";
                $html .= "<td>".Convert::raw2xml(implode(", ", ($parentNames)))."</td>";
                $html .= "<td>".Convert::raw2xml($stockist->Type)."</td>";
                $html .= "<td>".Convert::raw2xml($stockist->Title)."</td>";
                $html .= "<td>".Convert::raw2xml($stockist->City)."</td>";
                $html .= "<td>".Convert::raw2xml($stockist->WebAddress)."</td>";
                $html .= "<td>".Convert::raw2xml($stockist->Email)."</td>";
                $html .= "<td>".Convert::raw2xml($stockist->Phone)."</td>";
                $html .= "</tr>";
            }
        }
        return $html;
    }



    /**
     *
     * view list of stockists on blank screen with download link
     */
    public function listofstockists()
    {
        $html = "<h4><a href=\"".$this->Link("listofstockistscsv")."\">download csv file for Excel</a></h4>";
        $html .= $this->createListOfStockists();
        return $html;
    }


    private function createListOfStockists()
    {
        $html = "
        <table border=\"1\">
            <tr>
                <th>Region / Type</th>
                <th>Type</th>
                <th>Name</th>
                <th>City</th>
                <th>WebAddress</th>
                <th>Email</th>
                <th>Phone</th>
            </tr>";
        $html .= $this->getChildrenAsHTMLRows($this->Parent());
        $html .=   "</table>";
        return $html;
    }


    public function updatemap()
    {
        $pages = StockistPage::get()
            ->filter(array("HasGeoInfo" => 0));
        if ($pages && $pages->count()) {
            foreach ($pages as $page) {
                $page->write();
                $page->publish('Stage', 'Live');
            }
        }
        die("locations updated");
    }
}
