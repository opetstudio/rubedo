<?php
/**
 * Rubedo -- ECM solution
 * Copyright (c) 2013, WebTales (http://www.webtales.fr/).
 * All rights reserved.
 * licensing@webtales.fr
 *
 * Open Source License
 * ------------------------------------------------------------------------------------------
 * Rubedo is licensed under the terms of the Open Source GPL 3.0 license. 
 *
 * @category   Rubedo
 * @package    Rubedo
 * @copyright  Copyright (c) 2012-2013 WebTales (http://www.webtales.fr)
 * @license    http://www.gnu.org/licenses/gpl.html Open Source GPL 3.0 license
 */
use Rubedo\Services\Manager;

/**
 * Installer Controller
 *
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
class Backoffice_UpdateController extends Zend_Controller_Action
{

    public function indexAction ()
    {
        $rubedoDbVersionService = Manager::getService('RubedoVersion');
        
        $result = array(
            'needUpdate' => ! $rubedoDbVersionService->isDbUpToDate()
        );
        $this->_helper->json($result);
    }

    public function runAction ()
    {
        $result = array(
            'success' => true,
            'version'=> Rubedo\Update\Update::update()
        );
        $this->_helper->json($result);
    }
}

