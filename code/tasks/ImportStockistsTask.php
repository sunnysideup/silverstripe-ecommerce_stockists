<?php


/**
 * allows the creation of stockists from a CSV
 * data should be formatted in CSV like this:
 *
 * NAME (name of stockist)
 * COUNTRY - New Zealand
 * COUNTRYCODE - e.g. NZ, AU, US
 * TYPE (retailler / agent)
 * CITY - e.g. Amsterdam
 * WEB - e.g. http://www.mysite.co.nz
 * EMAIL
 * PHONE e.g. +31 33323321
 * ADDRESS
 * PHYSICAL (YES / NO)
 * ONLINE (YES / NO)

 */

class ImportStockistsTask extends BuildTask {

    protected $title = "Import all the stockists and link countries";

    protected $description = "
        Does not delete any record, it only updates and adds.
        ";

    /**
     * excluding base folder
     *
     * e.g. assets/files/mycsv.csv
     * @var String
     */
    protected $fileLocation = "_dev/_data/stockists.csv";

    /**
     * excluding base folder
     *
     * e.g. assets/files/mycsv.csv
     * @var String
     */
    protected $csvSeparator = ",";


    /**
     * @var Boolean
     */
    protected $debug = true;


    /**
     * the original data from the CVS
     * @var Array
     */
    protected $csv = array();

    function getDescription(){
        return $this->description ." The file to be used is: ".$this->fileLocation;
    }

    /**
     *
     */
    public function run($request){

        increase_time_limit_to(3600);
        set_time_limit(3600);
        increase_memory_limit_to('1024M');

        $this->readFile();

        $this->deleteAll();

        $this->createStockists();
    }

    protected function readFile(){


        DB::alteration_message("================================================ READING FILE ".ini_get('max_execution_time')."seconds available. ".(ini_get('memory_limit'))."MB available ================================================"); ob_start();

        $rowCount = 1;
        $rows = array();
        $fileLocation = Director::baseFolder()."/".$this->fileLocation;
        flush(); ob_end_flush(); DB::alteration_message("reading file $fileLocation", "deleted");ob_start();
        if (($handle = fopen($fileLocation, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 100000, $this->csvSeparator)) !== FALSE) {
                $cleanArray = array();
                foreach($data as $key => $value) {
                    $cleanArray[trim($key)] = trim($value);
                }
                $rows[] = $cleanArray;
                $rowCount++;
            }
            fclose($handle);
        }
        //$rows = str_getcsv(file_get_contents(, ",", '"');

        $header = array_shift($rows);

        $this->csv = array();
        $rowCount = 1;
        foreach ($rows as $row) {
            if(count($header) != count($row)) {
                flush(); ob_end_flush(); DB::alteration_message("I am trying to merge ".implode(", ", $header)." with ".implode(", ", $row)." but the column count does not match!", "deleted");ob_start();
                die("STOPPED");
            }
            $this->csv[] = array_combine($header, $row);
            $rowCount++;
        }
        flush(); ob_end_flush(); DB::alteration_message("Imported ".count($this->csv)." rows with ".count($header)." cells each");ob_start();
        flush(); ob_end_flush(); DB::alteration_message("Fields are: ".implode(", ", $header));ob_start();
        flush(); ob_end_flush(); DB::alteration_message("================================================");ob_start();

    }

    /**
     *
     *
     */
    protected function createStockists(){

        flush(); ob_end_flush(); DB::alteration_message("================================================ CREATING STOCKISTS ================================================");ob_start();
        $stockistsCompleted = array();
        $rootPage = StockistSearchPage::get()->filter(array("ClassName" => "ShopfinderPage"))->first();
        if(!$rootPage) {
            die("You must setup a stockist search page first");
        }
        flush(); ob_end_flush(); DB::alteration_message("<h2>Found Root Page: ".$rootPage->Title." (".$rootPage->ID.")</h2>");ob_start();
        $rowCount = 0;
        $continents = array();
        $countryCheckArray = array();
        $types = array();
        foreach($this->csv as $row) {
            $rowCount++;
            print_r($row);
            flush(); ob_end_flush(); DB::alteration_message("<h2>$rowCount: Creating stockist: ".$row["NAME"]."</h2>");ob_start();

            if(!isset($countryCheckArray[$row['COUNTRYCODE']])) {
                $countryPage = StockistCountryPage::get()
                    ->filter(array("CountryCode" => $row['COUNTRYCODE']))
                    ->first();
                $countryCheckArray[$row['COUNTRYCODE']] = $countryPage;
            }
            else {
                $countryPage = $countryCheckArray[$row['COUNTRYCODE']];
            }

            if(!$countryPage) {
                $countryPage = new StockistCountryPage();
                flush(); ob_end_flush(); DB::alteration_message(" --- Creating new country page page ".$row['COUNTRYCODE'], "created");ob_start();
            }

            else {
                flush(); ob_end_flush(); DB::alteration_message(" --- Existing country page ".$row['COUNTRYCODE'], "changed");ob_start();
            }

            $countryPage->Title = $row['COUNTRY'];
            $countryPage->MetaTitle = $row['COUNTRY'];
            $countryPage->MenuTitle = $row['COUNTRY'];
            $countryPage->URLSegment = $row['COUNTRY'];
            $countryPage->CountryCode = $row['COUNTRYCODE'];
            $countryPage->ParentID = $rootPage->ID;
            $countryPage->writeToStage("Stage");
            $countryPage->Publish('Stage', 'Live');

            //stockist page
            $stockistPage = StockistPage::get()->filter(array("Title" => $row["NAME"]))->first();
            if(!$stockistPage) {
                $stockistPage = new StockistPage();
                flush(); ob_end_flush(); DB::alteration_message(" --- Creating Stockist: ".$row["NAME"], "created");ob_start();
            }
            else {
                flush(); ob_end_flush(); DB::alteration_message(" --- Updating Stockist: ".$row["NAME"], "changed");ob_start();
            }
            $name = trim($row["NAME"]);
            $stockistPage->ParentID = $countryPage->ID;
            $stockistPage->Title = $name;
            $stockistPage->MenuTitle = $name;
            $stockistPage->URLSegment = $name;
            $stockistPage->Address = trim($row["ADDRESS"]);

            $stockistPage->Phone = trim($row["PHONE"]);
            $stockistPage->City = trim($row["CITY"]);
            $stockistPage->HasPhysicalStore =  1;
            $stockistPage->writeToStage('Stage');
            $stockistPage->Publish('Stage', 'Live');

            //types

            $type = "Retailer";

            flush(); ob_end_flush(); DB::alteration_message(" --- Adding type: ".$type, "changed");ob_start();

            if(!isset($types[$type])) {
                $typeObject = StockistPage_Type::get()->filter(array("Type" => $type))->first();
                $types[$type] = $typeObject;
            }
            else {
                $typeObject = $types[$type];
            }


        }
        flush(); ob_end_flush(); DB::alteration_message("====================== END ==========================");ob_start();
    }

    protected function deleteAll(){
        if(isset($_GET["reset"]) || isset($_GET["resetonly"])) {
            die("you have to manually remove this message to run ...");
            DB::alteration_message("Deleting all pages!", "deleted");
            $pages = SiteTree::get()->filter(array("ClassName" => array("StockistCountryPage", "StockistPage", "StockistSearchPage")));
            foreach($pages as $page) {
                $page->deleteFromStage("Live");
                $page->deleteFromStage("Stage");
                $page->delete();
            }
        }
    }


}
