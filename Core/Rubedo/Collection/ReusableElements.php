<?php
/**
 * Rubedo
 *
 * LICENSE
 *
 * yet to be written
 *
 * @category Rubedo
 * @package Rubedo
 * @copyright Copyright (c) 2012-2012 WebTales (http://www.webtales.fr)
 * @license yet to be written
 * @version $Id$
 */
namespace Rubedo\Collection;

use Rubedo\Interfaces\Collection\IReusableElements;

/**
 * Service to handle Reusable Elements
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
class ReusableElements extends AbstractCollection implements IReusableElements
{
	

	public function __construct(){
		$this->_collectionName = 'ReusableElements';
		parent::__construct();
	}
	
}