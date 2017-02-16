<?php

/**
 * GridField config necessary for managing a SiteTree object.
 *
 * @package silverstripe
 * @subpackage ecommerce_stockists
 */
class GridFieldConfig_StockistPage extends GridFieldConfig_Lumberjack
{
    /**
     * @param null|int $itemsPerPage
     */
    public function __construct($itemsPerPage = null)
    {
        parent::__construct($itemsPerPage);

        $this->removeComponentsByType('GridFieldSiteTreeState');
        $this->addComponent(new GridFieldBlogPostState());
    }
}
