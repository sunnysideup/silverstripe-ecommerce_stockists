<?php

class StockistPage extends Page
{

    /**
     * @inherited
     */
    private static $icon = 'mysite/images/treeicons/StockistPage';

    /**
     * @inherited
     */
    private static $allowed_children = 'none';

    /**
     * @inherited
     */
    private static $can_be_root = false;

    /**
     * @inherited
     */
    private static $default_parent = 'StockistCountryPage';



    /**
     * Standard SS variable.
     */
    private static $singular_name = "Stockist Page";
    public function i18n_singular_name()
    {
        return "Stockist Page";
    }

    /**
     * Standard SS variable.
     */
    private static $plural_name = "Stockist Pages";
    public function i18n_plural_name()
    {
        return "Stockist Pages";
    }

    /**
     * @inherited
     */
    private static $description = 'Individual Stockist Page';

    /**
     * @inherited
     */
    private static $db = array(
        'Address' => 'Varchar(255)',
        'StreetAddress' => 'Varchar(255)',
        'City' => 'Varchar(255)',
        'WebAddress' => 'Varchar(255)',
        'Email' => 'Varchar(255)',
        'Phone' => 'Varchar(255)',
        'Fax' => 'Varchar(255)',
        'HasPhysicalStore' => 'Boolean',
        'HasWebStore' => 'Boolean',
        'DefaultZoom' => 'Int'
    );

    /**
     * @inherited
     */
    private static $has_one = array(
        "Image" => "Image",
        "Logo" => "Image"
    );

    /**
     * @inherited
     */
    private static $many_many = array(
        "Types" => "StockistPage_Type"
    );

    /**
     * @inherited
     */
    private static $defaults = array(
        'HasGeoInfo' => true,
        'HasPhysicalStore' => true,
        'HasLighting' => true,
        'HasJewellery' => true,
        'DefaultZoom' => 15
    );

    /**
     * @inherited
     */
    private static $casting = array(
        'CountryName' => "Varchar",
        'CountryCode' => "Varchar",
        'DistributorName' => "Varchar",
        'PhoneWithoutSpaces' => 'Varchar'
    );

    /**
     * @inherited
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab('Root.Images', array(
            $imageField = new UploadField("Image", "Photo"),
            $logoField = new UploadField("Logo", "Logo")
        ));
        $imageField->setRightTitle("
            Upload 1400px wide x 630px high, at around 66% compression rate, images display up to 700px wide.
            This can typically be a picture of the store.
        ");
        $imageField->setFolderName("StockistImages");
        $logoField->setRightTitle("
            These are used primarily for webstores, but you can upload one for each stockist.
            Upload 400px wide x 185px high, at as much compression as looks good.
            Logos display up to 200px wide x 92.5px high.
            Logos should be uploaded as GIFs or PNGs (GIFs are smaller), with transparent background.
        ");
        $logoField->setFolderName("StokistLogos");

        //types
        $typeField = new CheckboxSetField(
            'Types',
            'Types',
            StockistPage_Type::get()->map()
        );

        $typeField->setRightTitle("What sort of customers does this shop serve?");

        $fields->addFieldsToTab('Root.Map', array(
            $fullAddressField = new TextField('Address'),
            $defaultZoomField = new NumericField('DefaultZoom'),
            $streetAddressField = new TextField('StreetAddress'),
            $cityField = new TextField('City'),
            $webAddress = new TextField('WebAddress', 'Web Address'),
            new EmailField('Email'),
            new TextField('Phone')
        ));

        $fullAddressField->setRightTitle('Full Address (including city and country)');
        $defaultZoomField->setRightTitle('Set between 1 and 20.  One is the whole world and twenty is highest zoom level for map.');
        $streetAddressField->setRightTitle('Number and Street');
        $webAddress->setRightTitle(' e.g. http://www.shop.com');
        $cityField->setRightTitle('Suburb and/or City and/or State');
        $fields->addFieldToTab('Root.Map', new ReadonlyField("CountryName"));
        $fields->addFieldToTab('Root.Map', new ReadonlyField("CountryCode"));
        $fields->addFieldToTab('Root.Map', new ReadonlyField("DistributorName"));
        if ($distributor = $this->getDistributor()) {
            $fields->addFieldToTab('Root.Map',
                new LiteralField("MyDistributorLink", "<h5><a href=\"".$distributor->CMSEditLink()."\">edit my " . _t('Distributor.SINGULAR_NAME', 'Distributor') . ' </a></h5>'));
        }
        return $fields;
    }

    /**
     * checks the map point
     * @inherited
     */
    public function onBeforeWrite()
    {
        $this->createMapPoint();
        parent::onBeforeWrite();
    }

    /**
     * checks the map details if it has a map...
     */
    public function createMapPoint()
    {
        if ($this->HasPhysicalStore && $this->Address) {
            if ($map = GoogleMapLocationsObject::get()
                ->filter(array("ParentID" => $this->ID))
                ->First()
            ) {
                //do nothing;
            } else {
                $map = new GoogleMapLocationsObject();
            }
            $map->PointType = "point";
            $map->ParentID = $this->ID;
            $map->Address = $this->Address;
            if ($map->findGooglePointsAndWriteIfFound()) {
                $this->HasGeoInfo = true;
            } else {
                $this->HasGeoInfo = false;
            }
        } else {
            $this->HasGeoInfo = false;
        }
    }

    /**
     *
     * @return String
     */
    public function CustomAjaxInfoWindow()
    {
        return $this->renderWith("StockistAddressOnMap");
    }

    /**
     * provides a links to Google Maps to search for directions
     * @return String
     */
    public function DirectionsLink()
    {
        if ($this->Address) {
            return "https://www.google.com/maps/dir//".urlencode($this->Address);
        }
    }

    /**
     * Obscure all email links in StringField.
     * Matches mailto:user@example.com as well as user@example.com
     *
     * @return string
     */
    public function EncodedEmailLink()
    {
        $obj = HideMailto::convert_email($this->Email, "Enquiry from www.davidtrubridge.com");
        return $obj->MailTo;
    }

    /**
     * Obscure all email links in StringField.
     * Matches mailto:user@example.com as well as user@example.com
     *
     * @return string
     */
    public function EncodedEmailText()
    {
        $obj = HideMailto::convert_email($this->Email, "Enquiry from www.davidtrubridge.com");
        return $obj->Text;
    }

    /**
     * @return Distributor
     */
    public function Distributor()
    {
        return $this->getDistributor();
    }
    public function getDistributor()
    {
        return Distributor::get_one_for_country($this->getCountryCode());
    }

    /**
     * @return Distributor
     */
    public function DistributorName()
    {
        return $this->getDistributorName();
    }
    public function getDistributorName()
    {
        if ($distributor = $this->Distributor()) {
            return $distributor->Name;
        }
    }

    public function CountryName()
    {
        return $this->getCountryName();
    }
    public function getCountryName()
    {
        return EcommerceCountry::find_title($this->getCountryCode());
    }
    /**
     * alias for getPointValues
     * @return String
     */
    public function PointValues($fieldNameArray = 'LocalityName')
    {
        return $this->getPointValues($fieldNameArray);
    }

    /**
     * returns, for example, an array for all the cities
     * for a stockist (based on their Geo Locations)
     * NB... values are cached...
     *
     * @param string $fieldName
     * @return array
     */
    public function getPointValues($fieldNameArray)
    {
        $safeFieldNameArray = array();
        foreach ($fieldNameArray as $fieldName) {
            $safeFieldName = Convert::raw2sql($fieldName);
            array_push($safeFieldNameArray, $safeFieldName);
        };

        $fieldNameArray = $safeFieldNameArray;

        $cachekey = "getPointField".'_'.$this->ID.'_'.implode('_', $fieldNameArray).'_'.preg_replace('/[^a-z\d]/i', '_', $this->LastEdited);
        $cache = SS_Cache::factory($cachekey);
        if (!($result = $cache->load($cachekey))) {
            $array = array();
            if ($this->HasGeoInfo) {
                $points = GoogleMapLocationsObject::get()
                    ->filter(array("ParentID" => $this->ID));

                if ($points->count()) {
                    foreach ($points as $point) {
                        $tempArray = array();
                        foreach ($fieldNameArray as $tempField) {
                            if (trim($point->$tempField) && !in_array($point->$tempField, $tempArray)) {
                                $tempArray[] = $point->$tempField;
                            }
                        }

                        $string = implode(', ', $tempArray);
                        if ($string) {
                            $array[$string] = $string;
                        }
                    }
                }
            }
            $cache->save(serialize($array), $cachekey);
            return $array;
        }

        return unserialize($result);
    }

    /**
     * @return String
     */
    public function CountryCode()
    {
        return $this->getCountryCode();
    }
    public function getCountryCode()
    {
        $parent = StockistCountryPage::get()->byID($this->ParentID);
        $x = 0;
        while ($parent && !$parent->CountryCode && $x < 10) {
            $parent = StockistCountryPage::get()->byID($parent->ParentID);
            $x++;
        }
        if (!$parent || !$parent->CountryCode) {
            return EcommerceConfig::get('EcommerceCountry', 'default_country_code');
        }
        return $parent->CountryCode;
    }

    public function getPhoneWithoutSpaces()
    {
        return preg_replace("/[^0-9+]/", "", $this->Phone);
    }

    public function types()
    {
        return "Retailer";
    }
}

class StockistPage_Controller extends Page_Controller
{
    public function init()
    {
        parent::init();
        $zoom = $this->DefaultZoom ? $this->DefaultZoom : 15;
        Config::inst()->update("GoogleMap", "default_zoom", $zoom);
        Config::inst()->update("GoogleMap", "title_div_id", "");
        $this->addMap("showpagepointsmapxml");
    }

    public function IsStockistPage()
    {
        return true;
    }
}
