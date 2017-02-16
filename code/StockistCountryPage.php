<?php

/**
 *
 *
 *
 *
 *
 *
 *
 */

class StockistCountryPage extends StockistSearchPage
{

    /**
     * @inherited
     */
    private static $icon = 'mysite/images/treeicons/StockistCountryPage';

    /**
     * @inherited
     */
    private static $db = array(
        'CountryCode' => 'Varchar(3)'
    );
    /**
     * @inherited
     */
    private static $indexes = array(
        'CountryCode' => true
    );

    /**
     * @inherited
     */
    private static $many_many = array(
        'AdditionalCountries' => 'EcommerceCountry'
    );

    /**
     * extended by lumberjack 
     * @var array
     */
    private static $extensions = array(
        'Lumberjack',
    );

    /**
     * @inherited
     */
    //private static $indexes = array(
    //	'Country' => array ( 'type' => 'unique', 'value' => 'Country' )
    //);

    /**
     * @inherited
     */
    private static $default_child = 'StockistPage';

    /**
     * @inherited
     */
    private static $allowed_children = array('StockistPage', 'StockistCountryPage');

    /**
     * @inherited
     */
    private static $can_be_root = false;

    /**
     * Standard SS variable.
     */
    private static $singular_name = "Stockist Country Page";
    public function i18n_singular_name()
    {
        return "Stockist Country Page";
    }

    /**
     * Standard SS variable.
     */
    private static $plural_name = "Stockist Country Pages";
    public function i18n_plural_name()
    {
        return "Stockist Country Pages";
    }

    /**
     * @inherited
     */
    private static $description = 'Stockist Country Page';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName("Map");
        $fields->removeFieldFromTab('Root.Main', 'Content');
        $countryArrayWithCodes = EcommerceCountry::get()->map("Code", "Name")->toArray();
        $countryArrayWithIDs = EcommerceCountry::get()->map("ID", "Name")->toArray();
        $title = singleton("EcommerceCountry")->i18n_singular_name();
        $countryField = new DropdownField('CountryCode', $title, array("" => " -- please select -- ") + $countryArrayWithCodes);
        $fields->addFieldsToTab('Root.Countries', $countryField);
        $fields->addFieldsToTab('Root.Countries', new CheckboxSetField('AdditionalCountries', 'Also for ', $countryArrayWithIDs));
        $gridField = new GridField(
            'StockistPage',
            'Stockists',
            $this->Children(),
            new GridFieldConfig_StockistPage()
        );
        $fields->addFieldsToTab('Root.Stockists', $gridField);
        return $fields;
    }


    public function validate()
    {
        if ($this->CountryCode) {
            $items = StockistCountryPage::get()
                ->filter(array("CountryCode" => $this->CountryCode))
                ->exclude(array("ID" => $this->ID));
            if ($items->count()) {
                $otherCountries = implode(", ", $items->map("ID", "Title")->toArray());
                return new ValidationResult(false, "Another country with the same country code already exists: ".$this->CountryCode." namely: ".$otherCountries.".  Please change the country.");
            }
        } elseif (!StockistSearchPage::get()->byID($this->ParentID)) {
            return new ValidationResult(false, "You need to add a country to any Stockist Country Page that is not a continent!  Continents are defined as pages that are children of the main stockist search page.");
        }
        return parent::validate();
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->CountryCode = strtoupper($this->CountryCode);
    }

    /**
     * returns an array of stockist types that can be shown in a map.
     * @return Array
     */
    public function getMapTypes()
    {
        $allTypes = singleton("StockistPage")->dbObject('Type')->enumValues();
        foreach ($allTypes as $key => $type) {
            if ($key == "Online Store") {
                unset($allTypes[$key]);
            }
        }
        return $allTypes;
    }

    /**
     *
     * create as many as you like
     * @inherited
     * @return Boolean
     */
    public function canCreate($member = null)
    {
        return true;
    }

    /**
     * @return Array
     */
    public function AllChildrenIDs()
    {
        $array[$this->ID] = $this->ID;
        $countries = $this->ChildCountries();
        foreach ($countries as $country) {
            $array[$country->ID] = $country->ID;
        }
        return $array;
    }

    /**
     * @return DataList | Null
     */
    public function ChildCountries()
    {
        return StockistCountryPage::get()->filter(array("ParentID" => $this->ID))->sort("Title");
    }

    /**
     * @return DataList | Null
     */
    public function AllPhysicalStockists()
    {
        return StockistPage::get()->filter(array("HasPhysicalStore" => true, "ParentID" => $this->AllChildrenIDs()));
    }

    /**
     * @return DataList | Null
     */
    public function AllOnlineStockists()
    {
        return StockistPage::get()->filter(array("HasWebStore" => 1, "ParentID" => $this->AllChildrenIDs()));
    }

    /**
     * alias for getStockistPointField
     * @return array
     */
    public function StockistPointValueList($fieldName = 'LocalityName')
    {
        return $this->getStockistPointValueList($fieldName);
    }


    /**
     * get a list of all values for one field for all stockists in this country
     * for example, all cities for Zimbabwe... (as defined by
     * its stockist pages AND the values saved in the stockists' geo locations.
     *
     * NB values are cached
     *
     * @param string $fieldName
     * @return array
     */
    public function getStockistPointValueList($fieldNames = 'LocalityName')
    {
        if (!is_array($fieldNames)) {
            $fieldNames = array($fieldNames);
        }
        $cachekey = "getStockistPointField".'_'.$this->ID.'_'.implode('_', $fieldNames);
        $cache = SS_Cache::factory($cachekey);
        if (!($result = $cache->load($cachekey))) {
            $stockists = StockistPage::get()->filter(array("ParentID" => $this->AllChildrenIDs()));
            $array = array();
            foreach ($stockists as $stockist) {
                $array = array_merge($array, $stockist->getPointValues($fieldNames));
            }
            sort($array);
            $cache->save(serialize($array), $cachekey);
            return $array;
        }

        return unserialize($result);
    }

    /**
     * @return ArrayList
     */
    public function Cities()
    {
        $headingsCreated = array();
        $al = ArrayList::create();
        $array = $this->getStockistPointValueList(array('AdministrativeAreaName', 'LocalityName'));
        foreach ($array as $fieldInfo) {
            $childrenArray = explode(',', $fieldInfo);
            $URLSegmentFromFieldInfo = implode(',', array_map('urlencode', $childrenArray));
            if (count($childrenArray) > 1) {
                foreach ($childrenArray as $key => $child) {
                    if ($key == 0) {
                        $primaryChild = $child;
                        if (! isset($headingsCreated[$primaryChild])) {
                            $headingsCreated[$primaryChild] = ArrayList::create();
                            $arrayData = array(
                                "ID" => preg_replace("/[^A-Za-z0-9 ]/", '-', $child),
                                "Title" => $child,
                                "Link" => "",
                                "HasChildren" => true,
                                "Children" => $headingsCreated[$primaryChild]
                            );
                            $al->push(
                                ArrayData::create($arrayData)
                            );
                        }
                    } else {
                        $arrayData = array(
                            "ID" => preg_replace("/[^A-Za-z0-9 ]/", '-', $child),
                            "Title" => $child,
                            "Link" => $this->Link("filter/LocalityName,AdministrativeAreaName/".$URLSegmentFromFieldInfo."/"),
                            "HasChildren" => false,
                            "Children" => null
                        );
                        $headingsCreated[$primaryChild]->push($arrayData);
                    }
                }
            } else {
                $arrayData = array(
                    "ID" => preg_replace("/[^A-Za-z0-9 ]/", '-', $fieldInfo),
                    "Title" => $fieldInfo,
                    "Link" => $this->Link("filter/LocalityName,AdministrativeAreaName/".$URLSegmentFromFieldInfo."/"),
                    "HasChildren" => false,
                    "Children" => null
                );
                $al->push(
                    ArrayData::create($arrayData)
                );
            }
        }
        $al->sort("City");
        return $al;
    }
}

class StockistCountryPage_Controller extends StockistSearchPage_Controller
{
    private static $allowed_actions = array(
        'filter'
    );


    public function init()
    {
        parent::init();
        if ($this->CountryCode) {
            $this->myCurrentCountryCode = $this->CountryCode;
        }
        //Requirements::customScript("jQuery(document).ready(function(){jQuery('#MapSidebar').show();});");
    }

    public function index()
    {
        $this->addMap("showchildpointsmapxml");
        return array();
    }

    /**
     * for template
     * @return Boolean
     */
    public function IsSearchPage()
    {
        return false;
    }

    public function filter($request)
    {
        $points = $this->locationsForCurrentCountry($request)->column("ID");
        $this->Title .= ' - '. urldecode($this->request->param("OtherID"));
        $this->addMap(
            $action = "showpointbyid",
            $title = $this->Title,
            $lng = 0,
            $lat = 0,
            implode(',', $this->locationsForCurrentCountry($request)->column("ID"))
        );

        return array();
    }

    public function locationsForCurrentCountry()
    {
        $fields = explode(",", $this->request->param("ID"));
        $values = explode(",", $this->request->param("OtherID"));
        if (count($fields) && count($values)) {
            $whereArrayOuter = array();
            $whereArrayOuterOuter = array();

            foreach ($values as $value) {
                if ($value) {
                    $whereArrayInner = array();
                    foreach ($fields as $field) {
                        $whereArrayInner[] = Convert::raw2sql(trim($field))." = '".Convert::raw2sql(trim($value))."'";
                    }
                    $whereArrayOuter[] = '('.implode(' OR ', $whereArrayInner).')';
                }
            }
            if (count($whereArrayOuter)) {
                $whereArrayOuterOuter[] = '('.implode(' AND ', $whereArrayOuter).')';
            }
            if (count($this->myCurrentCountryCode)) {
                $whereArrayOuterOuter[] = '("CountryNameCode" = \''.$this->myCurrentCountryCode.'\')';
            }
            $points = GoogleMapLocationsObject::get();
            if (count($whereArrayOuterOuter)) {
                $points = $points->where('('.implode(' ) AND (', $whereArrayOuterOuter).')');
            }
        } else {
            $points = GoogleMapLocationsObject::get()->filter(array("CountryNameCode" => $this->myCurrentCountryCode));
        }

        return $points;
    }
}
