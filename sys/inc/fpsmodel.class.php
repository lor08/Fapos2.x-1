<?php/*---------------------------------------------\|											   || @Author:       Andrey Brykin (Drunya)        || @Version:      1.1                           || @Project:      CMS                           || @package       CMS Fapos                     || @subpackege    FpsModel class                || @copyright     ©Andrey Brykin 2010-2012      || @last mod      2012/10/04                    ||----------------------------------------------||											   || any partial or not partial extension         || CMS Fapos,without the consent of the         || author, is illegal                           ||----------------------------------------------|| Любое распространение                        || CMS Fapos или ее частей,                     || без согласия автора, является не законным    |\---------------------------------------------*//** * Base class FpsModel. He is parent for all models. * Also he is something like DataMapper and simple Model. */abstract class FpsModel {    /**     * @var string     */	public $Table;    /**     * @var array     */	protected $has_one;    /**     * @var array     */	protected $has_many;    /**     * @var array     */	protected $Binded;    /**     * @var array     */    protected $BindedParams;	    /**     * @param $module     */	public function __construct()	{	}	    public function getTotal($params = array())   	{   		$cnt = $this->getDbDriver()->select($this->Table, DB_COUNT, $params);   		return $cnt;   	}	/**     * @param $id     * @return bool     */	public function getById($id)	{        $Register = Register::getInstance();		$entities = $this->getDbDriver()->select($this->Table, DB_FIRST, array(			'cond' => array(				'id' => $id			)		));		if ($entities && count($entities)) {            $entities = $this->getAllAssigned($entities);			$entityClassName = $Register['ModManager']->getEntityNameFromModel(get_class($this));			$entity = new $entityClassName($entities[0]);			return (!empty($entity)) ? $entity : false;		}		return false;	}	/**     * @return mixed     */	protected function getDbDriver()	{		$Register = Register::getInstance();		return $Register['DB'];	}	/** TODO     * @param $records     * @return bool     */	protected function getAllAssigned($records)	{		if (empty($records) || count($records) < 1) return false;        if (empty($this->Binded) || !is_array($this->Binded)) return $records;        $Register = Register::getInstance();        //pr($this->RelatedEntities);		// Get all IDs from records		$ids = array();        $hasOneKeys = array();		foreach ($records as $k => $r) {			$ids[$k] = $r['id'];			            // Also we must to collect foreign keys if they exists            if (!empty($this->RelatedEntities)) {                foreach ($this->RelatedEntities as $hok => $hov) {                    if ($hov['type'] !== 'has_one') continue;                    if (!in_array($hok, $this->Binded)) continue;                    if (!array_key_exists($hok, $hasOneKeys)) $hasOneKeys[$hok] = array();                    if (!empty($r[$hov['foreignKey']])) $hasOneKeys[$hok][$k] = $r[$hov['foreignKey']];                }            }		}		        // In this place we try get all assigned data by current entities        // further we merge this data with records(entities)        if (!empty($this->RelatedEntities)) {            foreach ($this->RelatedEntities as $relKey => $relVal) {                if (!in_array($relKey, $this->Binded)) continue;                if ($relVal['type'] === 'has_one') {                    $oModel = $Register['ModManager']->getModelInstance($relVal['model']);                    $oids = implode(', ', $hasOneKeys[$relKey]);					$hasOneData = array();					if (!empty($oids)) {						$where = array('`id` IN (' . $oids . ')');						if ($this->getBindParams($relKey)) {							$where = array_merge($where, $this->getBindParams($relKey));						}						$hasOneData = $oModel->getCollection($where);					}                    if (is_array($hasOneData) && count($hasOneData)) {                        foreach ($hasOneData as $ok => $ov) {                            foreach ($records as $rk => $rv) {                               if ($rv[$relVal['foreignKey']] == $ov->getId()) {                                   $records[$rk][$relKey] = $ov;                                   continue;                               }                            }                        }                    }                // and has_many...                } else if ($relVal['type'] === 'has_many') {                    $mModel = $Register['ModManager']->getModelInstance($relVal['model']);                    $mids = implode(', ', $ids);										$hasManyData = array();					if (!empty($mids)) {						$where = array('`' . $relVal['foreignKey'] . '` IN (' . $mids . ')');						if ($this->getBindParams($relKey)) {							$where = array_merge($where, $this->getBindParams($relKey));						}						$hasManyData = $mModel->getCollection($where);					}					                    if (!empty($hasManyData) && is_array($hasManyData)) {                        foreach ($hasManyData as $mk => $mv) {                            foreach ($records as $rk => $rv) {                               if (!array_key_exists($relKey, $records[$rk])) $records[$rk][$relKey] = array();                               if ($rv['id'] == $mv->{'get' . ucfirst($relVal['foreignKey'])}()) {                                   $records[$rk][$relKey][] = $mv;                                   continue;                               }                            }                        }                    } else {						foreach ($records as $rk => $rv) {							$records[$rk][$relKey] = array();						}					}                }            }        }        return $records;	}		// TODO (deprecated) Need change all bindModel calls	protected function __getAllAssigned($records)	{		if (empty($records) || count($records) < 1) return false;        if (empty($this->Binded) || !is_array($this->Binded)) return $records;        $Register = Register::getInstance();        //pr($this->RelatedEntities);		// Get all IDs from records		$ids = array();        $hasOneKeys = array();		foreach ($records as $k => $r) {			$ids[$k] = $r['id'];            // Also we must to collect foreign keys if they exists            if (!empty($this->RelatedEntities)) {                foreach ($this->RelatedEntities as $hok => $hov) {                    if ($hov['type'] !== 'has_one') continue;                    if (!in_array($hov['model'], $this->Binded)) continue;                    if (!array_key_exists($hok, $hasOneKeys)) $hasOneKeys[$hok] = array();                    if (!empty($r[$hov['foreignKey']])) $hasOneKeys[$hok][$k] = $r[$hov['foreignKey']];                }            }		}        // In this place we try get all assigned data by current entities        // further we merge this data with records(entities)        if (!empty($this->RelatedEntities)) {            foreach ($this->RelatedEntities as $relKey => $relVal) {                if (!in_array($relVal['model'], $this->Binded)) continue;                if ($relVal['type'] === 'has_one') {                    $oModel = $Register['ModManager']->getModelInstance($relVal['model']);                    $oids = implode(', ', $hasOneKeys[$relKey]);					$hasOneData = array();					if (!empty($oids)) {						$where = array('`id` IN (' . $oids . ')');						if ($this->getBindParams($relVal['model'])) {							$where = array_merge($where, $this->getBindParams($relVal['model']));						}						$hasOneData = $oModel->getCollection($where);					}                    if (count($hasOneData)) {                        foreach ($hasOneData as $ok => $ov) {                            foreach ($records as $rk => $rv) {                               if ($rv[$relVal['foreignKey']] == $ov->getId()) {                                   $records[$rk][$relKey] = $ov;                                   continue;                               }                            }                        }                    }                // and has_many...                } else if ($relVal['type'] === 'has_many') {                    $mModel = $Register['ModManager']->getModelInstance($relVal['model']);                    $mids = implode(', ', $ids);										$hasManyData = array();					if (!empty($mids)) {						$where = array('`' . $relVal['foreignKey'] . '` IN (' . $mids . ')');						if ($this->getBindParams($relVal['model'])) {							$where = array_merge($where, $this->getBindParams($relVal['model']));						}						$hasManyData = $mModel->getCollection($where);					}					                    if (!empty($hasManyData) && is_array($hasManyData)) {                        foreach ($hasManyData as $mk => $mv) {                            foreach ($records as $rk => $rv) {                               if (!array_key_exists($relKey, $records[$rk])) $records[$rk][$relKey] = array();                               if ($rv['id'] == $mv->{'get' . ucfirst($relVal['foreignKey'])}()) {                                   $records[$rk][$relKey][] = $mv;                                   continue;                               }                            }                        }                    } else {						foreach ($records as $rk => $rv) {							$records[$rk][$relKey] = array();						}					}                }            }        }        return $records;	}	    public function bindModel($modelName, $params = array())    {        if (empty($this->RelatedEntities) || !is_array($this->RelatedEntities)) return false;        foreach ($this->RelatedEntities as $relKey => $relEntity) {            if ($relKey === $modelName) {                $this->Binded[] = $modelName;                if (!empty($params)) $this->setBindParams($modelName, $params);                return true;            }        }        return false;    }    public function getBindParams($modelName = false)    {        if (!$modelName) return $this->BindedParams;        return ($this->BindedParams[$modelName]) ? $this->BindedParams[$modelName] : false;    }    public function setBindParams($modelName, $params)    {        $this->BindedParams[$modelName] = $params;    }    /**     * @param array $params     * @param array $addParams     * @return array|bool     */    public function getCollection($params = array(), $addParams = array())   	{        $Register = Register::getInstance();        $addParams['cond'] = $params;   		$entities = $this->getDbDriver()->select($this->Table, DB_ALL, $addParams);		   		if (!empty($entities)) {            $entities = $this->getAllAssigned($entities);   			$entityClassName = $Register['ModManager']->getEntityNameFromModel(get_class($this));            foreach ($entities as $key => $entity) {                $entities[$key] = new $entityClassName($entity);            }   			return (!empty($entities)) ? $entities : false;   		}   		return false;   	}    /**     * @param array $params     * @param array $addParams     * @return array|bool     */	public function getFirst($params = array(), $addParams = array())	{		$addParams['limit'] = 1;		$entities = $this->getCollection($params, $addParams);		return (!empty($entities) && is_array($entities) && count($entities) && isset($entities[0])) ? $entities[0] : false;   	}    /**     * @param $parentEntity     * @param $varName     * @return mixed     */    public function loadRelativeData($parentEntity, $varName)    {        $Register = Register::getInstance();        $relParams = $this->getRelatedEntitiesParams();        if (!count($relParams) || !array_key_exists($varName, $relParams)) return false;        $relParams = $relParams[$varName];        $ModelName = $Register['ModManager']->getModelName($relParams['model']);        $Model = new $ModelName($relParams['model']);        switch ($relParams['type']) {            case 'has_one':                $methodName = 'get' . ucfirst($relParams['foreignKey']);                $data = $Model->getById($parentEntity->$methodName());                break;            case 'has_many':                $params = array(                    $relParams['foreignKey'] => $parentEntity->getId(),                );                $data = $Model->getCollection($params);                break;        }        return $data;    }    /**     * @return array|bool     */    public function getRelatedEntitiesParams()    {        return (!empty($this->RelatedEntities)) ? $this->RelatedEntities : false;    }				public function getOneField($field, $params)	{		$output = array();		$result = $this->getDbDriver()->select($this->Table, DB_ALL, array(			'cond' => $params,			'fields' => array($field),		));				if (!empty($result)) {			foreach($result as $key => $record) {				$output[] = $record[$field];			}		}				return $output;	}				public function deleteByParentId($id)	{		$Register = Register::getInstance();		$where = array(			'entity_id' => $id,		);		//$records = $Register['DB']->select($this->Table, DB_ALL, array('cond' => $where));		$records = $this->getCollection($where);						if ($records) {			foreach ($records as $k => $v) {				$v->delete();			}		}	}			}