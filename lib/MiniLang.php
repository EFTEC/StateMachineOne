<?php
namespace eftec\statemachineone;

use Exception;
/**
 * It's a mini language parser. It uses the build-in token_get_all function to performance purpose.
 * Class MiniLang
 * @package eftec\statemachineone
 * @author   Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @version 1.12 2018-12-23
 * @link https://github.com/EFTEC/StateMachineOne
 */
class MiniLang
{
	/**
	 * When operators (if any)
	 * @var array 
	 */
	var $when=[];
	/**
	 * Set operators (if any
	 * @var array 
	 */
	var $set=[];
	private $specialCom=[];
	private $areaName=[];
	/** @var array values per the special area */
	var $areaValue=[];

	/**
	 * MiniLang constructor.
	 * @param array $specialCom Special commands. it calls a function of the caller.
	 * @param array $areaName It marks special areas that could be called as "<namearea> somevalue"
	 */
	public function __construct(array $specialCom=[],$areaName=[])
	{
		$this->specialCom = $specialCom;
		$this->areaName=$areaName;
	}


	public function reset() {
		$this->when=[];
		$this->set=[];
		//$this->areaName=[];
		//$this->areaValue=[];
	}

	/**
	 * @param $text
	 * @throws Exception
	 */
	public function separate($text) {
		$this->reset();
		$rToken=token_get_all("<?php ".$text);
		$par=[];
		$rToken[]=''; // avoid last operation
		$count=count($rToken)-1;
		$first=true;
		for($i=0;$i<$count;$i++) {
			$v=$rToken[$i];
			if(is_array($v)) {
				switch ($v[0]) {
					case T_CONSTANT_ENCAPSED_STRING:
						$this->addPar($par,$first,'string',substr($v[1],1,-1),null);
						break;
					case T_VARIABLE:
						if (is_string($rToken[$i+1]) && $rToken[$i+1]=='.') {
							// $var.vvv
							$this->addPar($par,$first,'subvar',substr($v[1],1),$rToken[$i+2][1]);
							$i+=2;
						} else {
							// $var
							$this->addPar($par,$first,'var',substr($v[1],1),null);
						}
						break;
					case T_LNUMBER:
						$this->addPar($par,$first,'number',$v[1],null);
						break;
					case T_STRING:
						if (in_array($v[1],$this->areaName)) {
							// its an area. <area> <somvalue>
							if (count($rToken)>$i+2) {
								$tk=$rToken[$i + 2];
								
								switch ($tk[0]) {
									case T_VARIABLE:
										$this->areaValue[$v[1]]=['var',$tk[1],null];
										break;
									case T_STRING:
										$this->areaValue[$v[1]]=['field',$tk[1],null];
										break;
									case T_LNUMBER:
										$this->areaValue[$v[1]]=$tk[1];
										break;
								}
							}
							$i+=2;
						} else {
							switch ($v[1]) {
								case 'where':
								case 'when':
									$par =& $this->when;
									break;
								case 'then':
								case 'set':
									$par =& $this->set;
									break;
								default:
									if (is_string($rToken[$i + 1])) {
										if ($rToken[$i + 1] == '.') {
											// field.vvv
											$this->addPar($par, $first, 'subfield', $v[1], $rToken[$i + 2][1]);
											$i += 2;
										} elseif ($rToken[$i + 1] == '(') {
											// function()
											$this->addPar($par, $first, 'fn', $v[1], null);
										} else {
											// field
											if (in_array($v[1], $this->specialCom)) {
												$this->addPar($par, $first, 'special', $v[1], null);
												$first = true;
											} else {
												$this->addPar($par, $first, 'field', $v[1], null);
											}

										}
									} else {
										// field
										$this->addPar($par, $first, 'field', $v[1], null);
									}
									break;
							}
						}
						break;
					case T_IS_GREATER_OR_EQUAL:
						$this->addOp($par,$first,'>=');
						break;
					case T_IS_SMALLER_OR_EQUAL:
						$this->addOp($par,$first,'<=');
						break;
					case T_IS_NOT_EQUAL:
						$this->addOp($par,$first,'<>');
						break;
					case T_LOGICAL_AND:
						$this->addLogic($par,$first,'and');
						break;
					case T_LOGICAL_OR:
						$this->addLogic($par,$first,'or');
						break;
				}
			} else {
				switch ($v) {
					case ',':
						$this->addLogic($par,$first,',');
						break;
					case '=':
					case '+':
					case '-':
					case '<':
					case '>':
						$this->addOp($par,$first,$v);
						break;
				}
			}
		}
	}

	/**
	 * @param mixed $caller
	 * @param array $dictionary
	 * @return bool|string it returns the evaluation of the logic or it returns the value special (if any).
	 * @throws Exception
	 */
	public function evalLogic(&$caller,$dictionary) {
		$prev=true;
		$r=false;
		$addType='';
		foreach($this->when as $k=>$v) {
			if($v[0]==='pair') {
				if ($v[1]=='special') {
					if (count($v)>=7) {
						return $caller->{$v[2]}($v[6]);
					} else {
						return $caller->{$v[2]}();
					}
				}
				$field0=$this->getValue($v[1],$v[2],$v[3],$caller,$dictionary);
				$field1=$this->getValue($v[5],$v[6],$v[7],$caller,$dictionary);
				switch ($v[4]) {
					case '=':
						$r = ($field0 == $field1);
						break;
					case '<>':
						$r = ($field0 != $field1);
						break;
					case '<':
						$r = ($field0 < $field1);
						break;
					case '<=':
						$r = ($field0 <= $field1);
						break;
					case '>':
						$r = ($field0 > $field1);
						break;
					case '>=':
						$r = ($field0 >= $field1);
						break;
					case 'contain':
						$r = (strpos($field0, $field1) !== false);
						break;
					default:
						trigger_error("comparison {$v[4]} not defined for eval logic.");
				}
				switch ($addType) {
					case 'and':
						$r=$prev && $r;
						break;
					case 'or':
						$r=$prev || $r;
						break;
					case '':
						break;
				}
				$prev=$r;
			} else {
				// logic
				$addType=$v[1];
			}
		} // for
		return $r;
	}
	/**
	 * @param mixed $caller
	 * @param array $dictionary
	 * @return void
	 * @throws Exception
	 */
	public function evalSet(&$caller,&$dictionary) {
		foreach($this->set as $k=>$v) {
			if($v[0]==='pair') {
				$name=$v[2];
				$ext=$v[3];
				$op=$v[4];
				//$field0=$this->getValue($v[1],$v[2],$v[3],$caller,$dictionary);
				$field1=$this->getValue($v[5],$v[6],$v[7],$caller,$dictionary);
				if ($field1==='___FLIP___') {
					$field0=$this->getValue($v[1],$v[2],$v[3],$caller,$dictionary);
					$field1=(!$field0)?1:0;
				}
				switch ($v[1]) {
					case 'subvar':
						// $a.field
						$rname=@$GLOBALS[$name];
						if (is_object($rname)) {
							$rname->{$ext}=$field1;
						} else {
							$rname[$ext]=$field1;
						}
						break;
					case 'var':
						// $a
						switch ($op) {
							case '=':
								$GLOBALS[$name]=$field1;
								break;
							case '+';
								$GLOBALS[$name]+=$field1;
								break;
							case '-';
								$GLOBALS[$name]-=$field1;
								break;
						}
						break;
					case 'number':
					case 'string':
						trigger_error("comparison {$v[4]} not defined for transaction.");
						break;
					case 'field':
						switch ($op) {
							case '=':
								$dictionary[$name]=$field1;
								break;
							case '+';
								$dictionary[$name]+=$field1;
								break;
							case '-';
								$dictionary[$name]-=$field1;
								break;
						}
						break;
					case 'subfield':
						//todo: subfields
						$dictionary[$name]=$field1;
						break;
					case 'fn':
						// function name($caller,$somevar);
						call_user_func($name, $caller,$field1);
						break;
					default:
						trigger_error("set {$v[4]} not defined for transaction.");
						break;
				}
			}
		} // for
	}
	public function getValue($type,$name,$ext,$caller,$dic) {
		switch ($type) {
			case 'subvar':
				// $a.field
				$rname=@$GLOBALS[$name];
				$r=(is_object($rname))?$rname->{$ext}:$rname[$ext];
				break;
			case 'var':
				// $a
				$r=@$GLOBALS[$name];
				break;
			case 'number':
				// 20
				$r=$name;
				break;
			case 'string':
				// 'aaa',"aaa"
				$r=$name;
				break;
			case 'field':
				$r=@$dic[$name];
				break;
			case 'subfield':
				//todo: subfields
				$r=@$dic[$name];
				break;
			case 'fn':
				switch ($name) {
					case 'null':
						return null;
					case 'false':
						return false;
					case 'true':
						return true;
					case 'on':
						return 1;
					case 'off':
						return 0;
					case 'undef':
						return -1;
					case 'flip':
						return "___FLIP___"; // value must be flipped (only for set).
					case 'now':
					case 'timer':
						return time();
					case 'interval':
						return time() - $caller->dateLastChange;
					case 'fullinterval':
						return time() - $caller->dateInit;
					default:
						return call_user_func($name, $caller);
				}
				break;
			case 'special':
				return $name;
				break;
			default:
				throw new Exception("value with type[$type] not defined");
		}
		return $r;
	}

	/**
	 * @param array $arr
	 * @param mixed $first
	 * @param string $type
	 * @param string $name
	 * @param null|string $ext
	 */
	private function addPar(&$arr,&$first,$type,$name,$ext=null) {
		if ($first) {
			$arr[]=['pair',$type,$name,$ext];
		} else {
			$f=count($arr)-1;
			$arr[$f][5]=$type;
			$arr[$f][6]=$name;
			$arr[$f][7]=$ext;
			$first=true;
		}
	}

	/**
	 * @param array $arr
	 * @param bool $first If it's true then it is the first value of a binary
	 * @param $name
	 * @throws Exception
	 */
	private function addOp(&$arr,&$first,$name) {
		if ($first) {
			$f=count($arr)-1;
			$arr[$f][4]=$name;
			$first=false;
		} else {
			throw new Exception("Error: Operation must be followed by a variable/function/constant");
		}
	}

	/**
	 * It adds a logic
	 * @param array $arr array where we want to add the logic
	 * @param bool $first If it's true then it is the first value of a binary
	 * @param string $name name of the logic
	 * @throws Exception
	 */
	private function addLogic(&$arr, &$first, $name) {
		if ($first) {
			$arr[]=['logic',$name];
		} else {
			throw new Exception("Error: Logic must be followed.");
		}
	}
}