Welder - HTML5 Form Builder for PHP
=======================================
v2.4.0

This library provides a simple powerful HTML5 form builder, validator, spam checker, spam submitter, form contents emailer, and more.

It is designed with the end user in mind so the typing is minimized as much as possible and the syntax is as much like HTML as possible with added functionality.

This library blocks form submissions from domains other than the domain the site is on. It also protects against Cross Site Request Forgeries by default.  

Install
-------

To install with composer:

```sh
composer require truecastdesign/welder
```

Requires PHP 5.5 or newer.

Usage
-----

How to build a form:

Make a new instance

```php
<?$F = new \Truecast\Welder?>
```

Output the form tag and other hidden fields

```php
<?=$F->start('action=/html-php-page class=formClassName')?>
```

The above code will output this HTML

```html
<form action="/html-php-page" method="post" class="formClassName" accept-charset="utf-8">
	<input type="hidden" name="authenticity_token" value="00c9f0fdca847c547ac0292d8d45e0e1ebcd5e395dffc4daf2a0b16420616cf2">
	<input type="hidden" name="form_action" value="submit">
```

File Uploads
---

If you are going to use File Uploads in the form, than you need to have the enctype set to multipart/form-data. To set that, pass file=true as an argument on the start method.


```php
<?=$F->start('action=/html-php-page class=formClassName file=true')?>
```

Text Fields
----------- 

The method names are the type of field you want. textarea, checkbox, etc. The double quotes in the property string are not needed if the value does not have spaces. Saves typing by skipping them if not needed.

```php
<?=$F->text('name=name label="Your Name *" style="width:250px" autofocus=autofocus pattern="^^([1-zA-Z0-1@.\s]{1,255})$" ');?>
```

Supported field types: text, password, hidden, submit, reset, image, file, number, email, tel, date, datetime, datetime-local, month, search, time, url, week, color, range, checkbox, radio, select, button.

The above code will output this HTML

```html
<span id="error-name" class="anchor"></span>
<label for="name_1">Your Name*</label> 
<input type="text" name="name" style="width:250px" autofocus="autofocus" pattern="^^([1-zA-Z0-1@.\s]{1,255})$" id="name_1">
```

Other examples:

```php
<?=$F->email('name=field_name label="The Label" style="width:250px"');?>
<?=$F->textarea('name=field_name label="The Label" style="width:250px"');?>
<?=$F->tel('name=field_name label="The Label" style="width:250px"');?>
```

Checkboxes
----------

```php
<?=$F->checkbox('name=checkBox label="Checkbox Label" value=Yes')?>
```

The above code will output this HTML

```html
<span id="error-checkBox" class="anchor"></span>
<input type="checkbox" name="checkBox" value="Yes" id="checkBox_1"> 
<label for="checkBox_1">Checkbox Label</label> 
```

Select Menus
------------

```php
<?=$F->select('name=selectMenu label="Select Label" selected=opt2 options="opt1:Option One| opt2:Option Two| opt3:Option Three"')?>
```

The above code will output this HTML

```html
<span id="error-selectMenu" class="anchor"></span>
<label for="selectMenu_1">Select Label</label> 
<select name="selectMenu" id="selectMenu_1">
	<option value="opt1">Option One</option>
	<option value="opt2" selected>Option Two</option>
	<option value="opt3">Option Three</option>
</select>
```

Custom Errors
-------------

Use the 'error' key to set a custom error to display when the form is submitted and validation errors are displayed.

```php
<?=$F->email('name=field_name label="The Label" error="Please enter a valid email address!"');?>
```


Form Validation
---------------

The validate method takes the field name on the left and the validation method to the right of the equal sign. The available validation methods start with validate_ in the class so you can look them up to see what they do. The clean method runs several content cleaning functions to sanitize the data if it does not conform to any set pattern like emails or names, etc.

You can use the spam method to check if the form is spam. If you want to use Akismet, just pass it the field names for their name, email, and content. If you want to make sure the form does not contain any urls then add the nourls flag. If you want to check the captcha add the captcha flag. Display the captcha on the page with &lt;?$F-&gt;captcha()?&gt;. You will need to move the form-captcha.php file to a public accessible directory and change the include path into to access the Welder.php file.

```php
$F = new Truecast\Welder; # does not need to be the same instance as the one used to build the form but can be.

if($F->validate('first_name=name email_address=email phone=clean message=required') and $F->spam('akismet=name,email,content nourls captcha')) # valid
{
	$values = $F->get(); # array of values from form cleaned and ready to insert into database or what ever.
	
	# email the form contents to yourself
	$F->emailForm(array('to_name'=>'Name', 'to_email'=>'name@gmail.com', 'from_name'=>$values['name'], 'from_email'=>$values['email'], 'subject'=>'Contact from Website', 'type'=>'html'), [name, email, phone, message]);
	
	# take them to the thanks page
	header("Location: /contact-us/thanks"); exit;
}
```

Getting and Setting Field values
---

If you want to populate the form fields from a database record or other source on display of form but not submitting it, use the setFieldValues method to pass a key value array of field name to value. You can set more than one at a time.

```php
$values = ['field1'=>'hello', 'field2'=>'world'];
$F->setFieldValues($values);
```

You can do this as an else statement on the if($F->validate()) method call if you make sure the view has access to the same Welder instance. Pass the $F variable to the view if needed.

```php
if($F->validate()) {
	// form submitted code
} else {
	// form loaded code
	$values = ['field1'=>'hello', 'field2'=>'world'];
	$F->setFieldValues($values);
}
```

To get a field value or all field values in the view use:

```php
<?=$F->getFieldValues('field1')?>
```

Get all in array.

```php
<? $array = $F->getFieldValues()?>
```


Configuration
---

## Turn off CSRF protection

```php
$F = new \Truecast\Welder(['csrf'=>false]);
```

## Turn off inline field errors

Normally there is outputted a span like:
```html
<span id="error-first_name" class="anchor"></span>
```
before each field. If you don't want this tag outputted for some reason, turn it off using the below code.

```php
$F = new \Truecast\Welder(['hide_field_error_tags'=>true]);
```

## Use more than one form in a view or controller

```php
$F = new \Truecast\Welder(['action_field'=>'submit1']);

$F2 = new \Truecast\Welder(['action_field'=>'submit2']);
```

You need to set the custom action field value on both the controller instance and the view instance so they match.
