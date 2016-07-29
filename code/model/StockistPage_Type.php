<?php

/**
 * define the type of stockists
 *
 *
 */

class StockistPage_Type extends DataObject {

    private static $db = array(
        "Code" => "Varchar(50)",
        "Title" => "Varchar(50)",
        "Description" => "Varchar(255)",
        'Type' => "Enum('Retailer,Agent', 'Retailer')",
        'SortNumber' => "Int"
    );

    private static $indexes = array(
        "Code" => true,
        "SortNumber" => true
    );

    private static $belongs_many_many = array(
        "StockistPages" => "StockistPage"
    );

    private static $summary_fields = array(
        "Title" => "Title",
        "Type" => "Type"
    );

    private static $default_sort = "SortNumber ASC";

    function canCreate($member = null) {
        return false;
    }

    function canEdit($member = null) {
        return parent::canEdit($member);
    }

    function canDelete($member = null) {
        $array = $this->dbObject('Type')->enumValues();
        if(!$this->Title) {
            return true;
        }
        if(isset($array[$this->Type])) {
            return false;
        }
        return parent::canDelete($member);
    }

    public function onBeforeWrite() {
        parent::onBeforeWrite();
        if(!$this->Code) {
            $this->Code = $this->Title;
        }
        if(!$this->Code) {
            $this->Code = Rand(1, 999999);
            $this->Title = Rand(1, 999999);
        }
        $this->Code = preg_replace('/[^\da-z ]/i', '', $this->Code);
        $this->Code = str_replace(' ', '_', $this->Code);
        $this->Code = strtolower($this->Code);
    }

    function Link($action = ""){
        $page = StockistSearchPage::get()->First();
        if($page) {
            return $page->Link("filterfor/".$this->Code."/");
        }
    }

    function getCMSFields(){
        $fields = parent::getCMSFields();
        $fields->addFieldToTab("Root.Main", $fields->dataFieldByName("Type")->performReadonlyTransformation());
        $fields->removeFieldFromTab("Root.Main", "SortNumber");
        return $fields;
    }

    function LinkingMode(){
        return "link";
    }

    public function requireDefaultRecords(){
        parent::requireDefaultRecords();
        $array = $this->dbObject('Type')->enumValues();
        $others = StockistPage_Type::get()->exclude(array("Type" => $array));
        foreach($others as $other) {
            DB::alteration_message("Deleting ".$other->Title." from StockistPage_Type", "deleted");
            $other->delete();
        }
        $count = 10;
        foreach($array as $key => $type) {
            $count = $count + 10;
            $type = trim($type);
            DB::alteration_message("CHECKING FOR '".$type."' from StockistPage_Type", "changed");
            $obj = StockistPage_Type::get()->filter(array("Type" => $type))->first();
            if(!$obj || !$obj->exists()) {
                $obj = new StockistPage_Type();
                $obj->Type = $type;
                $obj->Code = $type;
                $obj->Title = $type;
                $obj->Description = $type;
                DB::alteration_message("Creating ".$obj->Title." from StockistPage_Type", "created");
            }
            else {
                DB::alteration_message("FOUND ".$obj->Title." from StockistPage_Type", "changed");
            }
            $obj->SortNumber = $count;
            $obj->write();
        }
    }

}
