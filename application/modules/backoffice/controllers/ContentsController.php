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

require_once ('DataAccessController.php');

/**
 * Controller providing CRUD API for the field types JSON
 *
 * Receveive Ajax Calls for read & write from the UI to the Mongo DB
 *
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 *
 */
class Backoffice_ContentsController extends Backoffice_DataAccessController
{
	/**
	 * Array with the read only actions
	 */
	protected $_readOnlyAction = array('index', 'find-one', 'read-child', 'tree', 'clear-orphan-contents','count-orphan-contents','get-ordered-list');
	
    public function init() {
        parent::init();

        // init the data access service
        $this->_dataService = Rubedo\Services\Manager::getService('Contents');
    }

    /**
     * The default read Action
     *
     * Return the content of the collection, get filters from the request
     * params, get sort from request params
     *
     */
    public function indexAction() {
         // merge filter and tFilter
        $jsonFilter = $this->getParam('filter', Zend_Json::encode(array()));
        $jsonTFilter = $this->getParam('tFilter', Zend_Json::encode(array()));
        $filterArray = Zend_Json::decode($jsonFilter);
        $tFilterArray = Zend_Json::decode($jsonTFilter);
        $globalFilterArray = array_merge($tFilterArray, $filterArray);
        
        // call standard method with merge array
        $this->getRequest()->setParam('filter', Zend_Json::encode($globalFilterArray));
        
        $filters = Zend_Json::decode($this->getRequest()->getParam('filter', null));
        $mongoFilters = $this->_buildFilter($filters);
        $sort = Zend_Json::decode($this->getRequest()->getParam('sort', null));
        $start = Zend_Json::decode($this->getRequest()->getParam('start', null));
        $limit = Zend_Json::decode($this->getRequest()->getParam('limit', null));

        $dataValues = $this->_dataService->getList($mongoFilters, $sort, $start, $limit, false);

        $response = array();
        $response['total'] = $dataValues['count'];
        $response['data'] = $dataValues['data'];
        $response['success'] = TRUE;
        $response['message'] = 'OK';

        $this->_returnJson($response);
    }

    /**
     * read child action
     *
     * Return the children of a node
     *
     */
    public function readChildAction() {
        $filterJson = $this->getRequest()->getParam('filter');
        if (isset($filterJson)) {
            $filters = Zend_Json::decode($filterJson);
        } else {
            $filters = null;
        }
        $sortJson = $this->getRequest()->getParam('sort');
        if (isset($sortJson)) {
            $sort = Zend_Json::decode($sortJson);
        } else {
            $sort = null;
        }

        $parentId = $this->getRequest()->getParam('node', 'root');
        $mongoFilters = $this->_buildFilter($filters);
        $dataValues = $this->_dataService->readChild($parentId, $mongoFilters, $sort, false);

        $response = array();
        $response['children'] = array_values($dataValues);
        $response['total'] = count($response['children']);
        $response['success'] = TRUE;
        $response['message'] = 'OK';

        $this->_returnJson($response);
    }

    /**
     * The create action of the CRUD API
     */
    public function createAction() {
        $data = $this->getRequest()->getParam('data');
		
        if (!is_null($data)) {
            $insertData = Zend_Json::decode($data);
            if (is_array($insertData)) {
            	$insertData["target"]=isset($insertData["target"])?$insertData["target"]:array();
                $returnArray = $this->_dataService->create($insertData, array(), false);

            } else {
                $returnArray = array('success' => false, "msg" => 'Not an array');
            }
        } else {
            $returnArray = array('success' => false, "msg" => 'No Data');
        }
        if (!$returnArray['success']) {
            $this->getResponse()->setHttpResponseCode(500);
        }
        $this->_returnJson($returnArray);
    }

    /**
     * The update action of the CRUD API
     */
    public function updateAction() {

        $data = $this->getRequest()->getParam('data');

        if (!is_null($data)) {
            $updateData = Zend_Json::decode($data);
            if (is_array($updateData)) {

                $returnArray = $this->_dataService->update($updateData, array(), false);

            } else {
                $returnArray = array('success' => false, "msg" => 'Not an array');
            }
        } else {
            $returnArray = array('success' => false, "msg" => 'No Data');
        }
        if (!$returnArray['success']) {
            $this->getResponse()->setHttpResponseCode(500);
        }
        $this->_returnJson($returnArray);
    }
	
/**
	 * Do a findOneAction 
	 */
	public function findOneAction(){
		$contentId = $this->getRequest()->getParam('id');
		
		if(!is_null($contentId)){
			
			$return=$this->_dataService->findById($contentId,false,false);
			
			if(empty($return['id'])){
				
					$returnArray= array('success'=> false, "msg" => 'Object not found');
				
				}else {
					
					$returnArray=array('succes'=>true, 'data' => $return);
			
				}
				
		}else {
			
			$returnArray= array('success'=> false, "msg" => 'Missing param');
			
		}
		
			$this->_returnJson($returnArray);
	}
	
	/**
	 * Return a list of ordered objects
	 */
	public function getOrderedListAction() {
	    // merge filter and tFilter
	    $jsonFilter = $this->getParam('filter', Zend_Json::encode(array()));
	    $jsonTFilter = $this->getParam('tFilter', Zend_Json::encode(array()));
	    $filterArray = Zend_Json::decode($jsonFilter);
	    $tFilterArray = Zend_Json::decode($jsonTFilter);
	    $globalFilterArray = array_merge($tFilterArray, $filterArray);
	    
	    
	    
	    // call standard method with merge array
	    $this->getRequest()->setParam('filter', Zend_Json::encode($globalFilterArray));
	    
	    $filters = Zend_Json::decode($this->getRequest()->getParam('filter', null));
	    $sort = Zend_Json::decode($this->getRequest()->getParam('sort', null));
	    $start = Zend_Json::decode($this->getRequest()->getParam('start', null));
	    $limit = Zend_Json::decode($this->getRequest()->getParam('limit', null));
	    
	    $mongoFilters = $this->_buildFilter($filters);
	    $this->_helper->json($this->_dataService->getOrderedList($mongoFilters, $sort, $start, $limit, false));
	}
	
	public function clearOrphanContentsAction() {
		$result = $this->_dataService->clearOrphanContents();
		
		$this->_returnJson($result);
	}
	
	public function countOrphanContentsAction() {
		$result = $this->_dataService->countOrphanContents();
		
		$this->_returnJson($result);
	}
	
	public function deleteByContentTypeIdAction(){
	    $typeId = $this->getParam('type-id');
	    if(!$typeId){
	        throw new Rubedo\Exceptions\User('This action needs a type-id as argument.','Exception3');
	    }
	    $deleteResult = $this->_dataService->deleteByContentType($typeId);
	    
	    $this->_returnJson($deleteResult);
	}

}
