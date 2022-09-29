<?php
namespace Truecast;
/**
 * Form Builder and Validation class
 * 
 * @version v2.7.13
 *
<?
use Truecast\Welder;
$F = new Welder
?>

<?=$F->start('action=/register-for-events method=post class=registerForm')?>

<?=$F->text('name=name label="Your Name *" style="width:250px" autofocus required pattern="^^([1-zA-Z0-1@.\s]{1,255})$" ');?>

<?=$F->textarea('name=message label="Message *" style="width:550px"', $value);?>

<?=$F->checkbox('name=checkBox label="Checkbox Label" value=Yes')?>

<?=$F->select('name=selectMenu label="Select Label" options="opt1:Option One| opt2:Option, Two| opt3:Option, Three"')?>

OR

<?=$F->select('name=selectMenu label="Select Label"', ['label'=>'value', 'label 2'=>'value2'])?>

To set a default selected option other than the first one, add the property selected=(option value), example: selected=opt2

<?=$F->button('type=submit text="Send"')?>
 
-- FORM VALIDATION IN CONTROLLER --
$F = new \Truecast\Welder; # if you want to manually create the form, add a hidden field named "form_action" and value to "submit". If you want to change the form_action field value to something unique, pass it as a string when instigating the object. Example: $F = new TAFormBeta('custom_value'); <input type="hidden" name="form_action" value="custom_value"> 
The form field will automatically be added to the form if you use the $F->start() method call to generate your form.


if($F->validate('name=name email=email phone=clean message=required') and $F->spam('akismet="name,email,content" spamcontent="subject,message" nourls=true')) # valid
{
	$values = $F->get('object'); # array of values from form cleaned and ready to insert into database or what ever.
	
	$F->emailForm(array('to_name'=>'Name', 'to_email'=>'name@gmail.com', 'from_name'=>$values->name, 'from_email'=>$values->email, 'subject'=>'Contact from Website', 'type'=>'html'), [name, email, phone, message]);
	
	header("Location: /contact-us/thanks"); exit;
}
 *
 * @package True 6
 * @author Daniel Baldwin
 **/
class Welder
{
	static $nextId = 1;
	var $actionField;
	var $hideFieldErrorTags = false;
	var $form = null;
	var $valid = true;
	var $options = ['no_go_to_field_links'=>false];
	var $generalErrors = [];
	var $csrfSession = 'ksdfj3h9ehrjfh';
	var $csrfState = true;
	var $submitValues = [];
	
	/**
	 * construct
	 * Use the action_field only if you want to customize the form submission detection field if you have more than one form on a page or in a controller. The view and controller both need to be set and match.
	 * Use the csrf argument set to false on the controller construct if you want to disable CSRF protection. It is on by default so setting it to true does nothing.
	 *
	 * @param array $params ['action_field'=>'custom_value', 'csrf'=>false, 'hide_field_error_tags'=>true] 
	 */
	public function __construct($params=[])
	{
		if (isset($params['action_field'])) {
			$this->actionField = $params['action_field'];
		} else {
			$this->actionField = 'submit';
		}

		if (isset($params['hide_field_error_tags'])) {
			$this->hideFieldErrorTags = $params['hide_field_error_tags'];
		}		

		if (isset($params['csrf'])) {
			$this->csrfState = $params['csrf'];
		}
	}
	
	public function __call($type, $args)
	{
		$attributesStr = $args[0];
		$selectOptions = (isset($args[1])? $args[1]:[]);				
		
		$random = ''; $secure = false; $fieldProperties = ''; $name = '';
		
		$otherKeys[] = 'error';
		$otherKeys[] = 'rules';
		$otherKeys[] = 'default';
		$otherKeys[] = 'label'; 
		$otherKeys[] = 'options'; 
		$otherKeys[] = 'selected'; 
		$otherKeys[] = 'checked'; 
		$otherKeys[] = 'text'; 

		# single attribute keywords
		$singleAttributes[] = 'readonly';
		$singleAttributes[] = 'disabled';
		$singleAttributes[] = 'novalidate';
		$singleAttributes[] = 'autofocus';
		$singleAttributes[] = 'formnovalidate';
		$singleAttributes[] = 'multiple';
		$singleAttributes[] = 'required';
		$singleAttributes[] = 'checked';
		$singleAttributes[] = 'selected';

		# exclude from adding value attribute to tag
		$noValueAttribute[] = 'checkbox';
		$noValueAttribute[] = 'radio';

		foreach($singleAttributes as $attr)
		{
			$attSearch[] = $attr;
			$attReplace[] = $attr.'='.$attr;
		}

		$attributesStr = str_replace($attSearch, $attReplace, $attributesStr);
		
		$pairs = self::parse_csv(trim($attributesStr), ' ');
		
		# check if method is post or get
	   if(isset($_POST['form_action'])) 
	   {
	   	$this->submitValues = $_POST;
	   }	
	   elseif(isset($_GET['form_action']))
	   {
	   	$this->submitValues = array_map(function($str){
				return trim(strip_tags($str));
			}, $_GET);
	   }
		
		# get value
		if (isset($pairs['name'])) {
			$name = str_replace(['[',']'],'',$pairs['name']);
			
			if (isset($this->submitValues[$name]))
				$fieldValue = $this->submitValues[$name];
			elseif ($type == 'textarea' and isset($args[1]))
				$fieldValue = $args[1];
			elseif (isset($pairs['value']) and !empty($pairs['value']))
				$fieldValue = $pairs['value'];
			else
			 	$fieldValue = '';
		} else
			$fieldValue = '';
	   
		# save element
		$cleanedPairs = array_diff_key($pairs,array_flip($otherKeys));
		
		# check if there is an id, if not, then create one.
		if(!isset($cleanedPairs['id']))
		{
			if(!empty($name))
				$pairs['id'] = $cleanedPairs['id'] = $name.'_'.self::$nextId;
			else
				$pairs['id'] = $cleanedPairs['id'] = $type.'_'.self::$nextId;
			self::$nextId++;
		}

		# check for errors and add error class
		if(isset($this->form[$name]['error']))
		{
			if(empty($cleanedPairs['class']))
				$cleanedPairs['class'] = 'fieldError';
			else
				$cleanedPairs['class'] .= ' fieldError';
		}

		# check for textarea value key
		if ($type == 'textarea') {
			unset($cleanedPairs['value'], $pairs['value']);
		}
	
		foreach($cleanedPairs as $key=>$value)
		{
			if (in_array($key, $singleAttributes)) {
				$fieldProperties .= ' '.$key;	
				continue;
			}				
			
			$fieldProperties .= ' '.$key.'="'.$value.'"';				
		}
		
		switch($type)
		{
			case 'text':
			case 'password':
			case 'hidden':
			case 'submit':
			case 'reset':
			case 'image':
			case 'file':
			case 'number':
			case 'email':
			case 'tel':
			case 'date':
			case 'datetime':
			case 'datetime-local':
			case 'month':
			case 'search':
			case 'time':
			case 'url':
			case 'week':
			case 'color':
			case 'range':
				$fieldProperties .= ' value="'.htmlentities($fieldValue).'"';
				return $this->input($type, $pairs, $fieldProperties, htmlentities($fieldValue));
			break;
			case 'textarea':
				return $this->textarea($type, $pairs, $fieldProperties, $fieldValue);
			break;
			case 'select':
				return $this->select($type, $pairs, $fieldProperties, $fieldValue, $selectOptions);
			break;
			case 'checkbox':
				return $this->input($type, $pairs, $fieldProperties, $fieldValue);
			break;
			case 'radio':
				return $this->input($type, $pairs, $fieldProperties, $fieldValue);
			break;
			
			case 'button':
				if(!empty($fieldValue))
					$fieldProperties .= ' value="'.htmlentities($fieldValue).'"';

				return $this->button($pairs, $fieldProperties);
			break;
		}
	}
	
	/**
	 * Call this method to output their beginning of the form
	 *
	 * @param string $attributesStr 
	 * @return void
	 * @author Daniel Baldwin
	 */
	public function start($attributesStr = null)
	{
		$random = ''; $secure = false; $fieldProperties = ''; $str = '';
		
		if(PHP_SESSION_ACTIVE != session_status()) {
			session_start();
		}
			
		if ($this->csrfState) {
			if (function_exists('openssl_random_pseudo_bytes')) {
				$random = bin2hex(openssl_random_pseudo_bytes(32, $secure));
				# set session authenticity token
				$_SESSION[$this->csrfSession] = $random;
			}  	
			else {
				trigger_error('The function openssl_random_pseudo_bytes is not available in PHP!',256);
			}
		}				
		
		# parse $attributesStr
		$pairs = self::parse_csv(trim($attributesStr), ' ');
		
		# run checks on pairs
		if(!isset($pairs['method'])) 
		{
			$pairs['method'] = 'post';
		}	
		
		if(isset($pairs['file']) and $pairs['file']=='true')
		{
			$pairs['enctype'] = 'multipart/form-data';
			unset($pairs['file']);
		}	
		
		# if the form action is not provided, use the uri for the page
		if(!isset($pairs['action']))
		{
			# remove the query string if available
			if(isset($_SERVER["QUERY_STRING"]))
				$pairs['action'] = str_replace('?'.$_SERVER["QUERY_STRING"],'',$_SERVER["REQUEST_URI"]);
			else
				$pairs['action'] = $_SERVER["REQUEST_URI"];
		} 			
		
		# build properties
		foreach($pairs as $key=>$value)
		{
			if(!empty($key) and !empty($value))
				$fieldProperties .= ' '.$key.'="'.$value.'"';
		}
		
		# build html
		$str = '<form'.$fieldProperties.' accept-charset="utf-8">'."\n";
		
		if ($this->csrfState) {
			$str .= '<input type="hidden" name="authenticity_token" value="'.$random.'">'."\n";
		}

		$str .= '<input type="hidden" name="form_action" value="'.$this->actionField.'">'."\n";
		
		return $str;
	}
	
	/**
	 * Run this method to validate the form
	 *
	 * @param string $attributesStr, a string of field names and rules
	 * @param string $customErrors, a string of field names and custom error messages
	 * @return void
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function validate($fieldRulesStr, $customErrorsStr = null)
	{
		$submitValues = null; $formSubmitted = false;

		# check if method is post or get
	   if (!$this->submitted())
			return false;

		if (PHP_SESSION_ACTIVE != session_status()) {
			session_start();
		}
		
		if ($this->csrfState) {
			$token = $_SESSION[$this->csrfSession];
		}

		if (isset($_POST['form_action'])) {
			$submitValues = $_POST;
		}	
		elseif (isset($_GET['form_action'])) {
			$submitValues = array_map(function($str) {
				return trim(strip_tags($str));
			}, $_GET);
		}
		
		# check session authenticity token 
		if ($this->csrfState) {
			if (!empty($token) and !empty($submitValues['authenticity_token'])) {
				if (function_exists('hash_equals')) {
					if (hash_equals($token, $submitValues['authenticity_token']) === false) {
						$this->throwGeneralError("The authenticity token does not match what was in the form.");
						$this->valid = false;
					}
				} else {
					if ($token != $submitValues['authenticity_token']) {
						$this->throwGeneralError("The authenticity token does not match what was in the form.");
						$this->valid = false;
					}
				}
			} else {
				$this->throwGeneralError("Your authenticity token is missing.");
				$this->valid = false;
			}			
		}

		# parse $attributesStr
		$fieldRules = self::parse_csv(trim($fieldRulesStr), ' ');  

		# parse $customErrors
		$customErrors = self::parse_csv(trim($customErrorsStr), ' ');
		
		# validate the form data
		foreach ($fieldRules as $field=>$rules)
		{
			if (isset($rules))
			{
				$customErrorMsg = array_key_exists($field, $customErrors)? $customErrors[$field]:null;
				$fieldValue = array_key_exists($field, $submitValues)? $submitValues[$field]:null;

				$this->rules($field, explode('|', $rules), $fieldValue, $customErrorMsg);
			} 
				
		}

		if ( !empty($displayErrors = $this->errors()) )
			trigger_error($this->errors(),512); # display errors
	
		return ($this->valid)? true:false;
	}
	
	/**
	 * Check if the form is submitted or not
	 *
	 * @return bool true: the form is submitted, false: its not
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function submitted()
	{
		$formSubmitted = false;

		# check if method is post or get
	    if(isset($_POST['form_action'])) 
	    {
	    	if($_POST['form_action'] == $this->actionField)
	    	{
	    		$formSubmitted = true;
	    	}
	    }	
	    elseif(isset($_GET['form_action']))
	    {
	    	if($_POST['form_action'] == $this->actionField)
	    	{
	    		$formSubmitted = true;
	    	}	
	    }

	    return $formSubmitted;
	}

	/**
	* Set one or many field values
	*
	* @param array $value ['fieldname'=>'value'] you can set multiple fields
	* @return void 
	* @author Daniel Baldwin - danb@truecastdesign.com
	**/
	public function setFieldValue(array $value)
	{
		$this->submitValues = array_merge($this->submitValues, $value);
	}

	/**
	 * Get the value of one or all fields
	 *
	 * @param string $key field name. If not set, then an array of all field values will be returned
	 * @return string|array
	 */
	public function getFieldValue($key = null)
	{
		if (is_null($key)) {
			return $this->submitValues;
		}

		if (is_string($key)) {
			return $this->submitValues[$key];
		}
	}
	
	# get the form errors
	public function errors()
	{
		$errors = '';

		if(!is_array($this->form)) return false;

		foreach($this->form as $field=>$values) {
			if(isset($values['error'])) {
				$errors .= '<li>'.$values['error'].'</li>';
			}
		}
		
		if(count($this->generalErrors)) {
			foreach($this->generalErrors as $err) {
				if(!empty($err)) {
					$errors .= '<li>'.$err.'</li>';
				}
			}				
		}			
		
		if(!empty($errors)) {
			return '<ul>'.$errors.'</ul>';
		}
		else {
			return null;
		}			
	}

	/**
	 * Throw a general error
	 *
	 * @param 
	 * @return void
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function throwGeneralError($errorMsg)
	{
		$this->generalErrors[] = $errorMsg;
	}
	
	# get the form array after it's processed
	/**
	 * return the form values cleaned and validated
	 *
	 * @param bool|string $returnObj set to true if you want an value object return rather than an array or a better way use a string of object or array to be clear what to expect.
	 * @return array|object
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function get($returnObj = false)
	{
		if (!count($this->form)) return false;
		
		foreach($this->form as $field=>$values) 
			$form[$field] = $values['data'];
		
		if (is_string($returnObj)) 
			if ($returnObj == 'object')
				return (object) $form;
		
		if (is_bool($returnObj))
			if ($returnObj)
				return (object) $form;

		return $form;
	}
	
	# process the rules
	private function rules($field, $rules, $data, $errorMsg)
	{
		foreach($rules as $rule) # Loop through each rule and run it
		{
			$param = false;
			if (preg_match("/(.*?)\[(.*?)\]/", $rule, $match)) {
				$rule = $match[1];
				$param = $match[2];
			}

			$selfRule = 'validate_'.$rule;
						
			if (method_exists($this, $selfRule)) # check if the method exists for the rule
				$result = $this->$selfRule($data, $param); # Run the method and return the result	
			
			else  # there is a local method that matches
				if (function_exists($rule))
					$result = $rule($data);
				else 
					trigger_error("Invalid Rule: ".$rule."!",512);			

			# check if there was an error, if there was than if no custom error is set provide it
			if (is_bool($result) AND $result === false) { # invalid		
				if (!isset($errorMsg)) # no custom error
				     $this->form[$field]['error'] = $this->errorMsgs($rule, $field, $param);
				else # custom error
					$this->form[$field]['error'] = $errorMsg;

				$this->valid = false; # mark the form as not valid		
			}
			elseif ($result === true)
				$this->form[$field]['data'] = $data;
						
			# if the test returns content 
			if (!is_bool($result))
				$this->form[$field]['data'] = $data = $result;
		}
	}
	
	# @param (string) $rule - rule, (string) $field - form field name, (string) $param - value sent by rule
	private function errorMsgs($rule, $field, $param)
	{
		$matchField = null;
		$stripChars = array('_','-','*');
		
		$fieldLabel = ucwords(str_replace($stripChars,' ',$field));
		
		if($rule == 'matches') 
		{
			$matchField = ucwords(str_replace($stripChars,' ',$param));
		}	
		
		if($rule == 'depends')
		{
		    $parts = explode('=',$param);
    		$dependField = trim($parts[0]);
		} 
		else
		{
			$dependField = '';
		}
		
		# error messages. Feel free to change if needed.
		if($this->options['no_go_to_field_links'])
			$errors['required'] = "The ".$fieldLabel." field is required.";
		else
			$errors['required'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field is required. <a href='#error-".$field."'>Go to field.</a>";
		
		if($this->options['no_go_to_field_links'])
			$errors['name'] = "Your ".$fieldLabel." is required and is not valid.";
		else
			$errors['name'] = "Your <a href='#error-".$field."'>".$fieldLabel."</a> is required and is not valid. <a href='#error-".$field."'>Go to field.</a>";
		
		if($this->options['no_go_to_field_links'])
			$errors['matches'] = "The ".$fieldLabel." field does not match the ".$matchField." field.";
		else
			$errors['matches'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field does not match the ".$matchField." field. <a href='#error-".$field."'>Go to field.</a>";
		
		if($this->options['no_go_to_field_links'])
			$errors['depends'] = "The ".$fieldLabel." field is needed because the ".$dependField." field was answered.";
			else
			$errors['depends'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field is needed because the ".$dependField." field was answered. <a href='#error-".$field."'>Go to field.</a>";	
		
		if($this->options['no_go_to_field_links'])
			$errors['email'] = "Your ".$fieldLabel." is required and is not valid.";
		else
			$errors['email'] = "Your <a href='#error-".$field."'>".$fieldLabel."</a> is required and is not valid.  <a href='#error-".$field."'>Go to field.</a>";
		
		$errors['isset'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field must have a value. <a href='#error-".$field."'>Go to field.</a>";
		$errors['emails'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field must contain all valid email addresses. <a href='#error-".$field."'>Go to field.</a>";
		$errors['url'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field must contain a valid URL. <a href='#error-".$field."'>Go to field.</a>";
		$errors['ip'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field must contain a valid IP. <a href='#error-".$field."'>Go to field.</a>";
		$errors['min_length'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field must be at least ".$param." characters in length. <a href='#error-".$field."'>Go to field.</a>";
		$errors['max_length'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field can not exceed ".$param." characters in length. <a href='#error-".$field."'>Go to field.</a>";
		$errors['exact_length'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field must be exactly ".$param." characters in length. <a href='#error-".$field."'>Go to field.</a>";
		$errors['alpha'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field may only contain alphabetical characters. <a href='#error-".$field."'>Go to field.</a>";
		$errors['alpha_numeric'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field may only contain alpha-numeric characters. <a href='#error-".$field."'>Go to field.</a>";
		$errors['alpha_dash'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field may only contain alpha-numeric characters, underscores, and dashes. <a href='#error-".$field."'>Go to field.</a>";
		$errors['numeric'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field must contain only numbers. <a href='#error-".$field."'>Go to field.</a>";
		$errors['is_numeric'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field must contain only numeric characters. <a href='#error-".$field."'>Go to field.</a>";
		$errors['integer'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field must contain an integer. <a href='#error-".$field."'>Go to field.</a>";
		$errors['is_natural'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field must contain only positive numbers. <a href='#error-".$field."'>Go to field.</a>";
		$errors['is_natural_no_zero'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field must contain a number greater than zero. <a href='#error-".$field."'>Go to field.</a>";
		$errors['base64'] = "The <a href='#error-".$field."'>".$fieldLabel."</a> field contains invalid characters. <a href='#error-".$field."'>Go to field.</a>";
		$errors['cc'] = "Your <a href='#error-".$field."'>Credit Card number</a> is not valid. Please double check it. <a href='#error-".$field."'>Go to field.</a>";
		$errors['phone'] = "Your <a href='#error-".$field."'>Phone number</a> has invalid characters in it or is not filled in. Valid characters: numbers, spaces, pound, parentheses, and dashes. <a href='#error-".$field."'>Go to field.</a>";
		
	
		return ($errors[$rule]? $errors[$rule]:$errors['required']);
	}
	
	private function input($type, $pairs, $fieldProperties, $fieldValue)
	{	
		$labelAfter = ''; $labelBefore = ''; $checked = false; $errorSpan = '';
		# decide if label goes before or after input
		switch ($type)
		{
			case 'checkbox':
			case 'radio': 
				if (isset($pairs['label']))
					$labelAfter = $this->buildLabel($pairs['label'], $pairs['id']);
				else
					$labelAfter = '';
				
				if (isset($pairs['checked']) and empty($fieldValue))
					$checked = true;
				
				if (!is_array($fieldValue) and !empty($fieldValue))
					if ($fieldValue == $pairs['value'])
						$checked = true;
				elseif (is_array($fieldValue))
					if (in_array($pairs['value'], $fieldValue))
						$checked = true;	
			break;
			default:
				if (isset($pairs['label']) and isset($pairs['id']))
					$labelBefore = $this->buildLabel($pairs['label'], $pairs['id']);				
		}	

		if ($checked)
			$fieldProperties .= ' checked';

		if ($type != 'hidden' and !$this->hideFieldErrorTags)
		{
			if (isset($pairs['name']))
				$errorIdPart = $pairs['name'];
			elseif ($pairs['id'])
				$errorIdPart = $pairs['id'];
			
			$errorSpan = '<span id="error-'.$errorIdPart.'" class="anchor"></span>';
		}
		
		return $errorSpan.$labelBefore.' <input type="'.$type.'"'.$fieldProperties.'> '.$labelAfter;
	}
	
	private function textarea($name, $pairs, $fieldProperties, $fieldValue)
	{
		if (!isset($pairs['label']))
			$pairs['label'] = '';
		
		if (isset($pairs['name'])) {
			$errorIdPart = $pairs['name'];
		} elseif ($pairs['id']) {
			$errorIdPart = $pairs['id'];
		}
		
		if(!$this->hideFieldErrorTags)
		{
			$errorSpan = '<span id="error-'.$errorIdPart.'" class="anchor"></span>';
		}

		return $errorSpan.$this->buildLabel($pairs['label'], $pairs['id']).'<textarea'.$fieldProperties.'>'.$fieldValue.'</textarea>';
	}

	private function select($name, $pairs, $fieldProperties, $fieldValue, $selectOptions=[])
	{
		$html = $this->buildLabel($pairs['label'], $pairs['id']).'<select'.$fieldProperties.'>';

		#opt1:Option One| opt2:Option, Two| opt3:Option, Three
		#Group1{Option:Option| Option2:Option2}|
		if(!empty($pairs['options']))
		{
			
			$parts1 = explode('|', $pairs['options']);

			if(is_array($parts1))
			{
				foreach($parts1 as $opt)
				{
					$parts2 = explode(":", trim($opt));
					$options[trim($parts2[0])] = trim($parts2[1]);
				}
			}

			# build options
			if(is_array($options))
			{
				foreach ($options as $value => $label)
				{
					$selected = false;

					if (isset($pairs['selected'])) {
						if($pairs['selected'] == $value and empty($fieldValue))
							$selected = true;
					}

					# support for multiple select
					if(is_array($fieldValue))
					{
						if(!empty($value))
						{
							foreach($fieldValue as $selectedValue)
							{
								if($value == $selectedValue)
								$selected = true;
							}
						}
					}
					else # standard select menu
					{
						if(!empty($value) and !empty($fieldValue))
						{
							if($value == $fieldValue)
								$selected = true;
						}
					}
					

					$html .= '<option value="'.$value.'"'.($selected? ' selected="selected"':'').'>'.$label.'</option>'."\n";
				}
			}
		} elseif (isset($selectOptions) and count($selectOptions) > 0) {
			$selected = false;
			
			foreach ($selectOptions as $label=>$value) {
				if (is_array($value)) {
					$html .= '<optgroup label="'.$label.'">'."\n";
					foreach ($value as $optLabel=>$optValue) {						
						$selected = false;
						
						if (isset($pairs['selected'])) {
							if ($pairs['selected'] == $optValue and empty($fieldValue)) {
								$selected = true;
							}
						}
					
						# support for multiple select
						if (is_array($fieldValue))
						{
							if (!empty($optValue))
							{
								foreach ($fieldValue as $selectedValue)
								{
									if ($optValue == $selectedValue) {
										$selected = true;
									}
								}
							}
						}
						else # standard select menu
						{
							if(!empty($optValue) and !empty($fieldValue))
							{
								if ($optValue == $fieldValue) {
									$selected = true;
								}
							}
						}
					
						$html .= '<option value="'.$optValue.'"'.($selected? ' selected="selected"':'').'>'.$optLabel.'</option>'."\n";
					}
					
					$html .= '</optgroup>'."\n";
				} else {
					$selected = false;
						
					if (isset($pairs['selected'])) {
						if ($pairs['selected'] == $value and empty($fieldValue)) {
							$selected = true;
						}
					}
				
					# support for multiple select
					if (is_array($fieldValue))
					{
						if (!empty($value))
						{
							foreach ($fieldValue as $selectedValue)
							{
								if ($value == $selectedValue) {
									$selected = true;
								}
							}
						}
					}
					else # standard select menu
					{
						if(!empty($value) and !empty($fieldValue))
						{
							if ($value == $fieldValue) {
								$selected = true;
							}
						}
					}

					$html .= '<option value="'.$value.'"'.($selected? ' selected="selected"':'').'>'.$label.'</option>'."\n"; 
				}			
			}
		}

		$html .= '</select>';
		return $html;		
	}
	
	private function button($pairs, $properties)
	{
		return '<button'.$properties.'>'.$pairs['text'].'</button>';
	}
	
	private function buildLabel($text = '', $id = null)
	{
		$htmlAfterLabel = ''; $for = '';

		if(empty($text))
			return '';
		
		# if there is a | in the text then split it off
		if(strpos($text, '|') !== false)
		{
			$textParts = explode('|', $text);
			$text = $textParts[0];
			$htmlAfterLabel = $textParts[1];
		}			
		
		if($id != null)
			$for = ' for="'.$id.'"';
		
		return '<label'.$for.'>'.$text.'</label>'.$htmlAfterLabel;
	}
	
	/*This method restores the serialized form instance.*/
	private function recover($form) {
		if(is_array($_SESSION["TrueAdminForm"][$form]))
			return json_decode($_SESSION["TrueAdminForm"][$form]);
		else
			return array();
	}
	
	private function save($form, $field, $element) {
		$_SESSION["TrueAdminForm"][$form][$field] = json_encode($element);
	}
	
	private function getCSVValues($string, $separator=",")
	{
	    $elements = explode($separator, $string);
	    for ($i = 0; $i < count($elements); $i++) {
	        $nquotes = substr_count($elements[$i], '"');
	        if ($nquotes %2 == 1) {
	            for ($j = $i+1; $j < count($elements); $j++) {
	                if (substr_count($elements[$j], '"') > 0) {
	                    // Put the quoted string's pieces back together again
	                    array_splice($elements, $i, $j-$i+1,
	                        implode($separator, array_slice($elements, $i, $j-$i+1)));
	                    break;
	                }
	            }
	        }
	        if ($nquotes > 0) {
	            // Remove first and last quotes, then merge pairs of quotes
	            $qstr =& $elements[$i];
	            $qstr = substr_replace($qstr, '', strpos($qstr, '"'), 1);
	            $qstr = substr_replace($qstr, '', strrpos($qstr, '"'), 1);
	            $qstr = str_replace('""', '"', $qstr);
	        }
	    }
	    return $elements;
	}
	
	private static function parse_csv($csv_string = '', $delimiter = ",", $skip_empty_lines = true, $trim_fields = true)
	{
	    $attributes = [];

	    $enc = preg_replace('/(?<!")""/', '!!Q!!', $csv_string);
	    $enc = preg_replace_callback(
	        '/"(.*?)"/s',
	        function ($field) {
	            return urlencode(utf8_encode($field[1]));
	        },
	        $enc
	    );
	    $lines = preg_split($skip_empty_lines ? ($trim_fields ? '/( *\R)+/s' : '/\R+/s') : '/\R/s', $enc);
	    $array = array_map(
	        function ($line) use ($delimiter, $trim_fields) {
	            $fields = $trim_fields ? array_map('trim', explode($delimiter, $line)) : explode($delimiter, $line);
	            return array_map(
	                function ($field) {
	                    return str_replace('!!Q!!', '"', utf8_decode(urldecode($field)));
	                },
	                $fields
	            );
	        },
	        $lines
	    );
		
		if (is_array($array[0]))
		foreach($array[0] as $pair)
		{
			if(strpos($pair, '='))
			{
				list($key, $value) = explode('=',$pair);
				$attributes[$key] = $value;				
			}			
		}
		return $attributes;
	}
	
	private function parse($html='')
	{
		$dom = new \DOMDocument();
		@$dom->loadHTML($html);
		$x = new \DOMXPath($dom); 
		
		print_r($dom->childNodes);
		/*foreach($x->query("//a") as $node) 
		{
		    $data['dom']['href'][] = $node->getAttribute("href");
		} */
	}
	
	private function getmicrotime($t) {  
	 list($usec, $sec) = explode(" ",$t);  
	 return ((float)$usec + (float)$sec);  
	}  
	
	#### VALIDATION RULES ##########
	
	# Required
	function validate_required($str)
	{
		if(!is_array($str)) return (trim($str) == '')? FALSE:TRUE;
		else return (!empty($str));
	}
	
	# Match one field to another
	function validate_matches($str, $field)
	{
		if(!isset($_POST[$field])) return FALSE;			
		$value = $_POST[$field];
		return ($str !== $value)? FALSE:TRUE;
	}

	# Match one field to another
	# Use: field=in[value one,value two]
	function validate_in($str, $values)
	{
		$list = explode(',', $values);
		if (in_array($str, $list)) {
			return true;
		} else {
			return false;
		}
	}
	
	# Match one field to another
	# use: 'rule'=>'depends[field_name=value^second value]'
	function validate_depends($str, $fieldInfo)
	{
		$parts = explode('=',$fieldInfo);
		$dependField = trim($parts[0]);
		$dependValue = trim($parts[1]);
		$str = trim($str);

		if(strstr($dependValue, '^')) # more than one value
		{
		    $result = false;
		    $values = explode("^",$dependValue);
		    foreach($values as $val)
		    {
		        if($_POST[$dependField] == $val)
		        {
		            if($str=="") return false;
    		        else $result = true;
		        } 
		        else $result = true;
		    }
		    return $result;
		}
		else
		{
		    if($_POST[$dependField] == $dependValue)
		    {
		        if($str=="") return false;
		        else return true;
		    }
		    else return true;
		}
		return false;
	}
	
	# Minimum Length
	function validate_min_length($str, $val)
	{
		if(preg_match("/[^0-9]/", $val)) return FALSE;
		if(function_exists('mb_strlen')) return (mb_strlen($str) < $val)? FALSE:TRUE;
		return (strlen($str) < $val)? FALSE:TRUE;
	}
	
	# Max Length
	function validate_max_length($str, $val)
	{
		if(preg_match("/[^0-9]/", $val)) return FALSE;
		if (function_exists('mb_strlen')) return (mb_strlen($str) > $val) ? FALSE : TRUE;
		return (strlen($str) > $val) ? FALSE : TRUE;
	}
	
	# Exact Length
	function validate_exact_length($str, $val)
	{
		if (preg_match("/[^0-9]/", $val)) return FALSE;
		if(function_exists('mb_strlen')) return (mb_strlen($str) != $val) ? FALSE : TRUE;		
		return (strlen($str) != $val) ? FALSE : TRUE;
	}

	# Minimum and maximum Length
	function validate_range($str, $val)
	{
		if(preg_match("/[^0-9,]/", $val))
			throw new \Exception("Range value should be min,max format.");

		list($min,$max) = explode(',',$val);
		
		return (strlen($str) >= $min and strlen($str) <= $max)? true:false;
	}
	
	# Valid Email
	function validate_email($str)
	{
		if(filter_var($str, FILTER_VALIDATE_EMAIL)===false) return false;
		else return true;
		#return (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $str))? FALSE:TRUE;
	}
	
	# Valid Emails
	function validate_emails($str)
	{
		if (strpos($str, ',') === FALSE) return $this->email(trim($str));
		
		foreach(explode(',', $str) as $email)
		{
			if (trim($email) != '' && $this->email(trim($email)) === FALSE) return FALSE;
		}
		return TRUE;
	}
	
	# Alpha
	function validate_alpha($str)
	{
		return ( ! preg_match("/^([a-z])+$/i", $str)) ? FALSE : TRUE;
	}
	
	# Alpha-numeric
	function validate_alpha_numeric($str)
	{
		return ( ! preg_match("/^([a-z0-9])+$/i", $str)) ? FALSE : TRUE;
	}
	
	# Alpha-numeric with underscores and dashes
	function validate_alpha_dash($str)
	{
		return ( ! preg_match("/^([-a-z0-9_-])+$/i", $str)) ? FALSE : TRUE;
	}
	
	/**
	 * Numeric
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */	
	function validate_numeric($str)
	{
		return (bool)preg_match( '/^[\-+]?[0-9]*\.?[0-9]+$/', $str);

	}

	// --------------------------------------------------------------------

    /**
     * Is Numeric
     *
     * @access    public
     * @param    string
     * @return    bool
     */
    function validate_is_numeric($str)
    {
        return ( ! is_numeric($str)) ? FALSE : TRUE;
    } 

	// --------------------------------------------------------------------
	
	/**
	 * Integer
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */	
	function validate_integer($str)
	{
		return (bool)preg_match( '/^[\-+]?[0-9]+$/', $str);
	}
	
	// --------------------------------------------------------------------

    /**
     * Is a Natural number  (0,1,2,3, etc.)
     *
     * @access	public
     * @param	string
     * @return	bool
     */
    function validate_is_natural($str)
    {   
   		return (bool)preg_match( '/^[0-9]+$/', $str);
    }

	// --------------------------------------------------------------------

    /**
     * Is a Natural number, but not a zero  (1,2,3, etc.)
     *
     * @access	public
     * @param	string
     * @return	bool
     */
	function validate_is_natural_no_zero($str)
    {
    	if ( ! preg_match( '/^[0-9]+$/', $str))
    	{
    		return FALSE;
    	}
    	
    	if ($str == 0)
    	{
    		return FALSE;
    	}
    
   		return TRUE;
    }
	
	// --------------------------------------------------------------------
	
	/**
	 * Valid Base64
	 *
	 * Tests a string for characters outside of the Base64 alphabet
	 * as defined by RFC 2045 http://www.faqs.org/rfcs/rfc2045
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function validate_base64($str)
	{
		return (bool) ! preg_match('/[^a-zA-Z0-9\/\+=]/', $str);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Prep data for form
	 *
	 * This function allows HTML to be safely shown in a form.
	 * Special characters are converted.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function prep_for_form($data = '')
	{
		if (is_array($data))
		{
			foreach ($data as $key => $val)
			{
				$data[$key] = $this->prep_for_form($val);
			}
			
			return $data;
		}
		
		if($data === '') return $data;

		return str_replace(array("'", '"', '<', '>'), array("&#39;", "&quot;", '&lt;', '&gt;'), stripslashes($data));
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Prep URL
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */	
	function validate_prep_url($str = '')
	{
		if ($str == 'http://' OR $str == '') return '';
		if (substr($str, 0, 7) != 'http://' && substr($str, 0, 8) != 'https://') $str = 'http://'.$str;
		return $str;
	}
	
	// --------------------------------------------------------------------

	
	/**
	 * XSS Clean
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */	
	function validate_clean($str)
	{
		return utf8_encode(htmlspecialchars_decode(html_entity_decode(stripslashes($str))));
	}
	
	/**
	 * XSS Clean No backslashes stripping
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */	
	function validate_clean_no_stripslashes($str)
	{
		return utf8_encode(htmlspecialchars_decode(html_entity_decode($str)));
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Convert PHP tags to entities
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */	
	function validate_encode_php_tags($str)
	{
		return str_replace(array('<?php', '<?PHP', '<?', '?>'),  array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);
	}
	
	# check for a valid person's name. a-z . space -
	function validate_name($str) 
	{
		if($str == '') return false;
		return (bool) preg_match('/^[a-zA-Z0-9\. \-\'\,\&\#\(\)]*$/', $str);
	}
	
	# check for a valid person's name. a-z . space -
	function validate_address($str) 
	{
		if($str == '') return false;
		return (bool) preg_match('/^[0-9a-zA-Z\. \-\,\:]*$/', $str);
	}
	
	function validate_cc($str, $type)
	{
		$str = str_replace(array('-', ' '), '', $str);

		if(strlen($str) < 13) return false;

		$cards = array('all' => array('amex' => '/^3[4|7]\\d{13}$/',
									'bankcard' 	=> '/^56(10\\d\\d|022[1-5])\\d{10}$/',
									'diners'   	=> '/^(?:3(0[0-5]|[68]\\d)\\d{11})|(?:5[1-5]\\d{14})$/',
									'discover' 	=> '/^(?:6011|650\\d)\\d{12}$/',
									'electron' 	=> '/^(?:417500|4917\\d{2}|4913\\d{2})\\d{10}$/',
									'enroute'  	=> '/^2(?:014|149)\\d{11}$/',
									'jcb'      	=> '/^(3\\d{4}|2100|1800)\\d{11}$/',
									'maestro'  	=> '/^(?:5020|6\\d{3})\\d{12}$/',
									'mastercard' => '/^5[1-5]\\d{14}$/',
									'solo'     => '/^(6334[5-9][0-9]|6767[0-9]{2})\\d{10}(\\d{2,3})?$/',
									'switch'   => '/^(?:49(03(0[2-9]|3[5-9])|11(0[1-2]|7[4-9]|8[1-2])|36[0-9]{2})\\d{10}(\\d{2,3})?)|(?:564182\\d{10}(\\d{2,3})?)|(6(3(33[0-4][0-9])|759[0-9]{2})\\d{10}(\\d{2,3})?)$/',
									'visa'     => '/^4\\d{12}(\\d{3})?$/',
									'voyager'  => '/^8699[0-9]{11}$/'),
							'fast'   => '/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6011[0-9]{12}|3(?:0[0-5]|[68][0-9])[0-9]{11}|3[47][0-9]{13})$/');

		if(is_array($type))
		{
			foreach($type as $key => $value)
			{
				$card = strtolower($value);
				if((@preg_match($cards['all'][$card], $str) === false? false:true)) return $this->luhn($str);
			}
		} 
		else
		{
			if($type == 'all')
			{
				foreach ($cards['all'] as $key => $value)
				{
					if((@preg_match($value, $str) === false? false:true)) return $this->luhn($str);
				}
			} 
			else
			{
				if((@preg_match($cards['fast'], $str) === false? false:true)) return $this->luhn($str);
			}
		}
	}
	
	/* Luhn algorithm http://en.wikipedia.org/wiki/Luhn_algorithm */
	function validate_luhn($str)
	{
		if ($str == 0) return false;

		$sum = 0;
		$length = strlen($str);

		for ($position = 1 - ($length % 2); $position < $length; $position += 2)
		{
			$sum += substr($str, $position, 1);
		}

		for ($position = ($length % 2); $position < $length; $position += 2)
		{
			$number = substr($str, $position, 1) * 2;
			if ($number < 10) $sum += $number;
			else $sum += $number - 9;
		}

		if ($sum % 10 != 0) return false;
	}
	
	function validate_ip($ip)
	{
		return filter_var($ip, FILTER_VALIDATE_IP);
	}
	
	function validate_date($str, $format)
	{
		$regex['dmy'] = '%^(?:(?:31(\\/|-|\\.|\\x20)(?:0?[13578]|1[02]))\\1|(?:(?:29|30)(\\/|-|\\.|\\x20)(?:0?[1,3-9]|1[0-2])\\2))(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$|^(?:29(\\/|-|\\.|\\x20)0?2\\3(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:0?[1-9]|1\\d|2[0-8])(\\/|-|\\.|\\x20)(?:(?:0?[1-9])|(?:1[0-2]))\\4(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$%';
		$regex['mdy'] = '%^(?:(?:(?:0?[13578]|1[02])(\\/|-|\\.|\\x20)31)\\1|(?:(?:0?[13-9]|1[0-2])(\\/|-|\\.|\\x20)(?:29|30)\\2))(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$|^(?:0?2(\\/|-|\\.|\\x20)29\\3(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:(?:0?[1-9])|(?:1[0-2]))(\\/|-|\\.|\\x20)(?:0?[1-9]|1\\d|2[0-8])\\4(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$%';
		$regex['ymd'] = '%^(?:(?:(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00)))(\\/|-|\\.|\\x20)(?:0?2\\1(?:29)))|(?:(?:(?:1[6-9]|[2-9]\\d)?\\d{2})(\\/|-|\\.|\\x20)(?:(?:(?:0?[13578]|1[02])\\2(?:31))|(?:(?:0?[1,3-9]|1[0-2])\\2(29|30))|(?:(?:0?[1-9])|(?:1[0-2]))\\2(?:0?[1-9]|1\\d|2[0-8]))))$%';
		$regex['dMy'] = '/^((31(?!\\ (Feb(ruary)?|Apr(il)?|June?|(Sep(?=\\b|t)t?|Nov)(ember)?)))|((30|29)(?!\\ Feb(ruary)?))|(29(?=\\ Feb(ruary)?\\ (((1[6-9]|[2-9]\\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00)))))|(0?[1-9])|1\\d|2[0-8])\\ (Jan(uary)?|Feb(ruary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep(?=\\b|t)t?|Nov|Dec)(ember)?)\\ ((1[6-9]|[2-9]\\d)\\d{2})$/';
		$regex['Mdy'] = '/^(?:(((Jan(uary)?|Ma(r(ch)?|y)|Jul(y)?|Aug(ust)?|Oct(ober)?|Dec(ember)?)\\ 31)|((Jan(uary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sept|Nov|Dec)(ember)?)\\ (0?[1-9]|([12]\\d)|30))|(Feb(ruary)?\\ (0?[1-9]|1\\d|2[0-8]|(29(?=,?\\ ((1[6-9]|[2-9]\\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00)))))))\\,?\\ ((1[6-9]|[2-9]\\d)\\d{2}))$/';
		$regex['My'] = '%^(Jan(uary)?|Feb(ruary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep(?=\\b|t)t?|Nov|Dec)(ember)?)[ /]((1[6-9]|[2-9]\\d)\\d{2})$%';
		$regex['my'] = '%^(((0[123456789]|10|11|12)([- /.])(([1][9][0-9][0-9])|([2][0-9][0-9][0-9]))))$%';
		$regex['yyyy-mm-dd'] = '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/';
		
		return (bool) preg_match($regex[$format], $str);
	}
	
	function validate_age($str)
	{
		list($Y,$m,$d) = explode("-",$str);
		return (date("md") < $m.$d ? date("Y")-$Y-1 : date("Y")-$Y );
	}
	
	function validate_ssn($str, $country='us') 
	{
		switch($country)
		{
			case 'us':
				$regex  = '/\\A\\b[0-9]{3}-[0-9]{2}-[0-9]{4}\\b\\z/i';
			break;
			case 'dk':
				$regex  = '/\\A\\b[0-9]{6}-[0-9]{4}\\b\\z/i';
			break;
			case 'nl':
				$regex  = '/\\A\\b[0-9]{9}\\b\\z/i';
			break;
		}
		return (bool) !preg_match($regex, $str);
	}
	
	function validate_phonenumbersonly($str)
	{
		if($str == '') return false;
    	$numbersOnly = preg_replace("[^0-9]", "", $str);
        $numberOfDigits = strlen($numbersOnly);
        if($numberOfDigits == 7 OR $numberOfDigits == 10 OR $numberOfDigits == 11) return true;
        else return false;
	}
	
	function validate_phone($str)
	{
		if($str == '') return false;
		return (bool) preg_match('/^[0-9\. \-\#\(\)]*$/', $str);
	}
	
	# Pass the result of a custom function into validator 
	function inject($str, $result)
	{
		if($result==1) return true;
		else return false;
	}
	
	/**
	 * Validate URL
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */	
	function validate_url($str)
	{
		if(filter_var($str, FILTER_VALIDATE_URL)===false) return false;
		else return true;
		#return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $str);
	}
	
	
	/**
	 * Form emailer
	 *
	 * @param array $settings array('to_name'=>'', 'to_email'=>'', 'from_name'=>'', 'from_email'=>'', 'subject'=>'', 'type'=>html|csv, 'header'=>'', 'footer'=>'', 'email_header'=>'').
	 * @param array $fields list of fields that need to be sent. Leave blank for all.
     * @return bool
	 * @author Dan Baldwin
	 */
	function emailForm($settings=array(), $fields=null)
	{
		$extraHeaders = '';
		$attachmentData = '';
		$valuesData = '';
		
		if(!is_array($fields))
		{
			foreach($this->elements as $key=>$ele)
			{
				$fields[] = $key;
			}
		} 
		
		unset($fields['action'],$fields['submit']);
		
		$values = $this->get(); 
		#\nX-Mailer: Microsoft Office Outlook 12.0
		
		switch($settings['type'])
		{
			case 'html':
				$extraHeaders .= "\r\nMIME-Version: 1.0\nContent-type: text/html; charset=UTF-8\nContent-Language: en-us";
				
				$eBody = $settings['header'];
				foreach($fields as $key)
				{
					if($values[$key]) $eBody .= str_replace('_',' ',ucwords($key)).': '.nl2br(htmlspecialchars_decode(html_entity_decode(trim(stripslashes($values[$key])))),false).'<br/><br/>';
				}
				$eBody .= $settings['footer'];
			break;
			
			case 'csv':
				$random_hash = md5(date('r', time()));
				$extraHeaders .= "\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed;\r\n  boundary=\"mixed-".$random_hash."\"";

				$mainBody = $settings['header']."<br/>";
				foreach($fields as $key)
				{
					if($values[$key]) $mainBody .= str_replace('_',' ',$key).': '.trim(stripslashes($values[$key])).'<br/><br/>';
				}
				$mainBody .= $settings['footer'];

				$c = count($fields);
				for($i=0; $i<$c; $i++) 
				{ 
					if($i) {$attachmentData .= ','; $valuesData .= ',';}
					$attachmentData .= '"'.htmlspecialchars_decode(html_entity_decode(trim(stripslashes(str_replace('!','',str_replace(' ','_',$fields[$i])))))).'"';
					$valuesData .= '"'.htmlspecialchars_decode(html_entity_decode(trim(stripslashes($values[$fields[$i]])))).'"';
				}
				$attachmentData .= "\r\n".$valuesData;

				$eBody = "--mixed-".$random_hash."\r\n".
				"Content-Type: multipart/alternative; boundary=\"alt-".$random_hash."\"\r\n\r\n".
				"--alt-".$random_hash."\r\n".
				"Content-Type: text/html; charset=\"UTF-8\"\r\n".
				"Content-Transfer-Encoding: 7bit\r\n\r\n".
				$mainBody."\r\n\r\n".
				"--alt-".$random_hash."--\r\n\r\n".
				"--mixed-".$random_hash."\r\n".
				"Content-Type: application/excel; name=\"".$random_hash.".csv\"\r\n".
				"Content-Transfer-Encoding: 7bit\r\n".
				"Content-Disposition: attachment\r\nfilename=\"".$random_hash.".csv\"\r\n\r\n".
				$attachmentData."\r\n".
				"--mixed-".$random_hash."--\r\n";
			break;
			
			default:
				$eBody = $settings['header']."\n\n";
				foreach($fields as $key)
				{
					$eBody .= ucwords($key).": ".htmlspecialchars_decode(html_entity_decode(stripslashes($values[$key])))."\n\n";
				}
				$eBody .= "\n".$settings['footer'];
		}

		if(isset($settings['email_header']))
			$extraHeaders = "\r\n".$settings['email_header']."\r\n".$extraHeaders;
		
		if($settings['to_name']) $to = '"'.$settings['to_name'].'" <'.$settings['to_email'].'>';
		else $to = $settings['to_email'];
		
		return mail($to, $settings['subject'], $eBody, 'From: "'.$settings['from_name'].'" <'.$settings['from_email'].'>'.$extraHeaders);	
	}

	/**
	 * build and return an html email body with the form fields
	 *
	 * @param array|object $values  key=>value pairs, "field name"=>"field value"
	 * @param array $fields  simple array of field names you want in the email
	 * @return string html email
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function buildEmailBody($values, $fields)
	{
		$eBody = '';

		if(!isset($fields))
		{
			trigger_error("Fields were not sent", 256);
			return false;
		}

		if(!isset($values))
		{
			trigger_error("Form values were not sent", 256);
			return false;
		}

		$values = (object) $values;

		if(is_array($fields))
		foreach($fields as $key)
		{
			if($values->{$key}) 
				$eBody .= str_replace('_',' ',ucwords($key)).': '.nl2br(htmlspecialchars_decode(html_entity_decode(trim(stripslashes($values->{$key})))),false).'<br><br>';
		}

		return $eBody;
	}
	
	function textToHTML($str)
	{
		return preg_replace("\n\r?\n", "<br>", htmlspecialchars($str));
	}

	/**
	 * simple br tag to ln converter for converting 
	 *
	 * @param string $text
	 * @return string
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function HTMLToText($text)
	{
		return strip_tags(str_ireplace(["<br />","<br>","<br/>"], "\n", str_replace(["\r", "\n"], '', $text)));
	}

	/**
	 * main spam checking method to call when validating the form
	 *
	 * @param string 'spamcontent=[comma separated list of fields to check] akismet=[comma separated list of fields for with name then email, then content] nourls=[with field list or not] captcha'
	 * @return bool true if passes, false it fails (it's spam)
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function spam($attributesStr='')
	{
		#check if form is submitted or not
		if (!$this->submitted()) return false;
		
		if (isset($_GET['form_action'])) $_POST = $_GET;

		$result = true;
		$contentInfo = [];
		# expected values: 'spamcontent=message,name akismet=name,email,content nourls=true captcha'

		# parse $attributesStr
		$pairs = self::parse_csv(trim($attributesStr), ' ');

		$akismet = array_key_exists('akismet', $pairs)? true:false;
		
		# make array of author and message fields for akismet
		if ($akismet) 
			list($contentInfo["author"], $contentInfo["author_email"], $contentInfo["content"]) = explode(',',$pairs['akismet']);

		# check akismet, fields, and host forging
		if ($this->spamTest($akismet, $contentInfo)) $result = false;
		
		# check for urls
		if (array_key_exists('nourls', $pairs)) {
			$fields = explode(',',$pairs['nourls']);
			$urlResult = false;

			if ($pairs['nourls'] == 'true') {
				foreach ($_POST as $k => $v)
				{
					if (!isset($_POST[$k])) continue;

					if ($this->urlDetect($v)) {
						$result = false;
						$urlResult = true;
					}
				}
			} elseif (count($fields) > 0) {
				foreach ($fields as $field) {
					$field = trim($field);
					if (!isset($_POST[$field])) continue;

					if ($this->urlDetect($_POST[$field])) {
						$result = false;
						$urlResult = true;						
					}
				}
			}

			if ($urlResult) {
				trigger_error("Spam type: URLs Not Allowed!", 512);
			}
		}

		if (array_key_exists('spamcontent', $pairs)) {
			$fields = explode(',',$pairs['spamcontent']);
			$spamGibberish = false;
			$spamTooManyConsonants = false;
			$spamKeywordSearch = false;

			# run the 3 checks on each fields given
			if (is_array($fields)) {
				foreach ($fields as $field) {
					if (isset($_POST[$field])) {
						if (\Truecast\Gibberish::test($_POST[$field])) {
							$spamGibberish = true;
							$result = false;
						}	

						if ($this->tooManyConsonants($_POST[$field])) {
							$spamTooManyConsonants = true;
							$result = false;
						}	

						if ($this->keywordSearch($_POST[$field])) {
							$spamKeywordSearch = true;
							$result = false;
						}
						
						if ($this->htmlInField($_POST[$field])) {
							$spamHTML = true;
							$result = false;
						}	
					}	
				}
			}

			if ($spamGibberish) trigger_error("Spam type: Gibberish!", 512);
			if ($spamTooManyConsonants) trigger_error("Spam type: Too Many Consonants Together!", 512);
			if ($spamKeywordSearch) trigger_error("Spam type: Keyword Search!", 512);
			if ($spamHTML) trigger_error("Spam type: HTML in message!", 512);			
		}

		if($result == false)
			trigger_error("The form you submitted appears to be SPAM. Please do not SPAM our website. If this is a legitimate submission, please remove spammy content and try again.", 512);
		else
			trigger_error("Not Spam", 1024);

		return $result;			
	}
	
	/**
	 * spamTest
	 *
	 * @param string $host - domain.com of your website. If form is submitted to handler from another server it will block it.
	 * @param array $contentInfo - array('author'=>, 'author_email'=>, 'content'=>)
	 * @param string $akismetKey - If you want to use your own key, insert it here
	 * @param string $akismet - default is on. False to turn it off
	 * @return bool true (its SPAM), false (its HAM)
	 * @author Dan Baldwin
	 */
	public function spamTest($akismet=false, $contentInfo)
	{
	    $akismetKey = "1638dc33068b";
		$host = '';
		
		if(!isset($_SERVER['HTTP_USER_AGENT']) OR !$_SERVER['REQUEST_METHOD'] == "POST"){
			return true;
		}
		#$host = strtolower(str_replace('www.','',$host));

		$authHosts[] = strtolower(str_replace('www.','',$_SERVER['SERVER_NAME']));
		#else $authHosts = $host;
		$fromArray = parse_url(strtolower($_SERVER['HTTP_REFERER']));
		$wwwUsed = strpos($fromArray['host'], "www.");
        
		if(!in_array(($wwwUsed === false ? $fromArray['host'] : substr(stristr($fromArray['host'], '.'), 1)), $authHosts))
		{   
			return true;  
		}

		// Attempt to defend against header injections:
		$badStrings = array("Content-Type:", "MIME-Version:", "Content-Transfer-Encoding:", "bcc:", "cc:", "to:");

		// Loop through each POST'ed value and test if it contains
		// one of the $badStrings:
		foreach($_POST as $k => $v)
		{
			foreach($badStrings as $v2)
			{
				if(strpos($v, $v2) !== false)
				{
					return true;
				}
			}
		}   

		// Made it past spammer test, free up some memory
		// and continue rest of script:   
		unset($k, $v, $v2, $badStrings, $authHosts, $fromArray, $wwwUsed, $host);
		
        if($akismet)
        {
            $AK = new \Truecast\WelderAkismet($akismetKey, "http://".$host);
            
            if(!is_array($contentInfo)) echo 'Akismet Error: Supplied content is not an array.';
            
            $contentData = array(
					'user_ip' => $_SERVER['REMOTE_ADDR'],
					'user_agent' => $_SERVER['HTTP_USER_AGENT'],
					'referrer' => $_REQUEST['REFERER'],
					'comment_type' => 'error_report',
					'comment_author' => $contentInfo["author"],
					'comment_author_email' => $contentInfo["author_email"],
					'comment_content' => $contentInfo["content"]);
            
            return $AK->check_comment($contentData); # is spam if returns true 
        }
        
        return false;
	}

	/**
	 * detects if there is a url in the message text
	 *
	 * @param string $value - message text
	 * @return bool true if urls are detected
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function urlDetect($value='')
	{
		preg_match('/www\.|http:|https:\/\/[a-z0-9_]+([\-\.]{1}[a-z_0-9]+)*\.[_‌​a-z]{2,5}'.'((:[0-9]‌​{1,5})?\/.*)?$/i', $value, $matches);

		preg_match("/[-a-zA-Z0-9@:%_\+.~#?&\/=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~#?&\/=]*)?/i", $value, $matches2);

		return (count($matches) > 0 OR count($matches2) > 0)? true:false;
	}

	/**
	 * check if the message has words will more than 6 consonants in a row. Usually means it is invalid words and spam
	 *
	 * @param string message text
	 * @return bool spam if true
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function tooManyConsonants($value='')
	{
		preg_match('/[bcdfghjklmnpqrstvwxz]{6}/i', $value, $matches);

		if(count($matches) > 0)
			return true;
		else
			return false;
	}

	/**
	 * search message for spam keywords
	 *
	 * @param string - $value - message to search
	 * @return bool - true if we think it is spam
	 * @author Daniel Baldwin - danb@truecastdesign.com
	 **/
	public function keywordSearch($value='')
	{
		$keywords = require 'keywords.php';	
		$hit = false;

		foreach ($keywords as $key) {
			if (stripos($value, $key) !== false) {
				$hit = true;
			}	
		}
		return $hit;
	}

	public function htmlInField($value='')
	{
		return (strip_tags($value) != $value)? true:false;
	}
	
	/**
	 * Submit spam messages to Akismet
	 *
	 * @param array $values array('author', 'author_email', 'content')
	 * @return void
	 * @author Daniel Baldwin
	 */
	public function submitSpam($values)
	{
		$akismetKey = "1638dc33068b";
		
		$AK = new \Truecast\WelderAkismet($akismetKey, "http://".$_SERVER["HTTP_HOST"]);
		
     	$post_args['comment_author'] = $values["author"];
     	$post_args['comment_author_email'] = $values["author_email"];
     	$post_args['comment_content'] = $values["content"];
		
		$AK->submit_spam($post_args);
	}
	
	/**
	 * Call this method to display captcha image in the form where you want it. $Form->captcha();
	 *
	 * @return html string
	 * @author Daniel Baldwin
	 */
	public function captcha()
	{
		if(isset($_SESSION))
		return '<div class="taCaptchaBox"><div><img src="/admin/models/captcha/taFormCaptcha.php" alt="captcha image"/></div>
			<div><b>Type the letters or numbers:</b><br/><input type="text" name="taCaptcha" value="" id="taCaptcha" size="7"/></div></div>';
		else return "System Error: Sessions were not started! Please add session_start(); to the top of your file.";
	}
	
	/**
	 * create the captcha image and display it to the page. 
	 *
	 * @return void
	 * @author Daniel Baldwin
	 */
	public function createCaptcha()
	{
		# get key for captcha
		$key = $this->secretKey(6);
		
		# save the key in a session
		$_SESSION['taFormCaptcha'] = $key;
		
		# create the captcha image
		$img = $this->captchaImage($key, $img_width=180, $img_height=38);
		
		# set headers
		header("Content-type: image/png");
		header('Cache-Control: no-cache');
		header('Pragma: no-cache');
		
		# output to browser as PNG image
		@imagepng($img);
		@imagedestroy($img);
	}
	
	/**
	 * check to see if what the user typed matches saved key
	 *
	 * @param string $key 
	 * @return bool true|false true it matches or false it does not
	 * @author Daniel Baldwin
	 */
	public function checkCaptcha($key)
	{
		return (strtolower($key) == strtolower($_SESSION['taFormCaptcha'])? true:false);
	}	
	
	public function captchaImage($secret, $img_width=150, $img_height=40)
	{
	  // seed srand
	  srand((double)microtime()*1000000);

	  // create image
	  $im = @imagecreate($img_width, $img_height) or die("Cannot Initialize new GD image stream");

	  // define font
	  # use fc-list in SSH to get a list of fonts available
	  $font = "AHGBold.ttf";

	  // create some colors
	  $black = imagecolorallocate($im, 0, 0, 0);
	  $white = imagecolorallocate($im, 255, 255, 255);
	  $grey = imagecolorallocate($im, 128, 128, 128);

	  // randomness, we need lots of randomness :)
	  // background color -> 1=black, 2=white, 3=grey (more colors can be added)
	  // lines -> black bg (1=white or 2=grey), white bg (1=black or 2=grey), grey bg (black only)
	  $randval = rand(1, 3);
	  if ($randval == 1) {
	    $bgcolor = $black;
	    $fontcolor = $white;
	    $linecolor = ((rand(0, 1) == 0) ? $black : $white);
	  } elseif ($randval == 2) {
	    $bgcolor = $white;
	    $fontcolor = $black;
	    $linecolor = ((rand(0, 1) == 0) ? $black : $white);
	  } else {
	    $bgcolor = $grey;
	    $fontcolor = $black;
	    $linecolor = ((rand(0, 1) == 0) ? $black : $grey);
	  }

	  // line positioning and increment
	  $x_start = rand(0, 10);
	  $x_size = rand(5, 10);
	  $y_start = rand(0, 10);
	  $y_size = rand(5, 10);

	  // fill with background color
	  imagefill($im, 0, 0, $bgcolor);

	  // initial x position
	  $font_x = 10;

	  // write text
	  for ($i = 0; $i < strlen($secret); $i++) {
	    // font size -> 20 to 35
	    $font_size = rand(18, 25);
	    // font angle -> -20 to +20
	    $font_angle = rand(0, 20);
	    if ($font_angle != 0) { if (rand(0, 1) == 0) { $font_angle = -$font_angle; } }
	    // font y position -> if font_size <= 27 then 30 to 35, if font_size > 27 then 30 to 35
	    if ($font_size <= 27) { $font_y = rand(25, 30); } else { $font_y = rand(30, 35); }
	    // write the text
	    imagettftext($im, $font_size, $font_angle, $font_x, $font_y, $fontcolor, $font, $secret[$i]);
	    // one more time to make it bolder
	    imagettftext($im, $font_size, $font_angle, $font_x+1, $font_y+1, $fontcolor, $font, $secret[$i]);
	    // next font x position
	    $font_x += ($font_size + 5);
	  }

	  // draw horizontal lines
	  for ($y = $y_start; $y < $img_height; $y += $y_size) {
	    imageline($im, 0, $y, $img_width, $y, $linecolor);
	  }
	  // draw vertical lines
	  for ($x = $x_start; $x < $img_width; $x += $x_size) {
	    imageline($im, $x, 0, $x, $img_height, $linecolor);
	  }

	  // return captcha image handle
	  return $im;
	}
	
	/**
	 * Secret Key generator
	 *
	 * @param int $length 
	 * @return string secret key
	 * @author Daniel Baldwin
	 */
	private function secretKey($length=32)
	{
		return random_bytes($length);
	}
	
	private function is_assoc($arr)
	{
		return (is_array($arr) && count(array_filter(array_keys($arr),'is_string')) == count($arr));
	}
	
	/**
	 * convert text to be used as an id
	 *
	 * @param string $string 
	 * @return string
	 * @author Daniel Baldwin
	 */
	private function textToId($string)
	{    
	    $string = str_replace(" ","-", trim($string));         
	    $string = preg_replace("/[^a-zA-Z0-9-]/","", $string);
	    $string = strtolower($string);
	    return $string;
	}
	
	/**
	 * str position check function
	 *
	 * @param string $haystack 
	 * @param array $needle 
	 * @param int $offset 
	 * @return bool true|false
	 * @author Daniel Baldwin
	 */
	private function strposa($haystack, $needle, $offset=0)
	{
	    if(!is_array($needle)) $needle = array($needle);
	    foreach($needle as $query) {
	        if(strpos($haystack, $query, $offset) !== false) return true; // stop on first true result
	    }
	    return false;
	}
}