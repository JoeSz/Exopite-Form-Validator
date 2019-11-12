# Exopite Form Validator

Inspired by: https://github.com/ASoares/PHP-Form-Validation

This class is an extension of ASoares/PHP-Form-Validation class.<br>
Class and readme based on https://github.com/ASoares/PHP-Form-Validation

---

 A simple, flexible and easy to use PHP form validation class
  (uses a fluent interface )


**Note:** index.php  has a typical example ,  if anyone decides to use this , please double check the spelling on error messages ;-)

@author Andre Soares  andsoa77@gmail.com

**License:**

GPL v2 http://www.gnu.org/licenses/gpl-2.0.txt


**typical use:**

    $valid = new ValidFluent($_POST);
    $valid->name('user_name')->required('You must chose a user name!')->alfa()->minSize(5);
    $valid->name('user_email')->required()->email();
    $valid->name('birthdate')->date('please enter date in YYYY-MM-DD format');
    if ($valid->isGroupValid()) echo 'Validation Passed!';

 **OR:**

    $valid = new ValidFluent($_POST);
  	if ( $valid->name('user_name')->required('You must chose a user name!')->alfa()->minSize(5)
  		    ->name('user_email')->required()->email()
  		    ->name('birthdate')->date('please enter date in YYYY-MM-DD format')
  		    ->isGroupValid() )
  	    echo 'Validation passed!';


  **On HTML Form:**
  <form method="POST">

  	    <input type="text"   name="email"
  		   value="<?php echo $valid->getValue('email'); ?>" />
  	    <span class="error">
  		<?php echo $valid->getError('email'); ?>
  	    </span>




#  To create new validation rules!

**1- define default error message**

    private static $error_myValidaton = 'my default error message';

**2- create new validation function**

    function myValidation($param , $errorMsg=NULL)
      {
      if ($this->isValid && (! empty($this->currentObj->value)))
	    {
	    	//
	    	//code to check if validation pass
	    	//
	   	$this->isValid = // TRUE or FALSE ;
		if (! $this->isValid)
		$this->setErrorMsg($errorMsg, self::$error_myValidation, $param);
    	}
      return $this;
      }

**3- use it**

    $Valid->name('testing')->myValidation(10, 'some error msg!');

SUPPORT/UPDATES/CONTRIBUTIONS
-----------------------------

If you use my program(s), I would **greatly appreciate it if you kindly give me some suggestions/feedback**. If you solve some issue or fix some bugs or add a new feature, please share with me or mke a pull request. (But I don't have to agree with you or necessarily follow your advice.)

**Before open an issue** please read the readme (if any :) ), use google and your brain to try to solve the issue by yourself. After all, Github is for developers.

My **updates will be irregular**, because if the current stage of the program fulfills all of my needs or I do not encounter any bugs, then I have nothing to do.

**I provide no support.** I wrote these programs for myself. For fun. For free. In my free time. It does not have to work for everyone. However, that does not mean that I do not want to help.

I've always tested my codes very hard, but it's impossible to test all possible scenarios. Most of the problem could be solved by a simple google search in a matter of minutes. I do the same thing if I download and use a plugin and I run into some errors/bugs.

DISCLAMER
---------

NO WARRANTY OF ANY KIND! USE THIS SOFTWARES AND INFORMATIONS AT YOUR OWN RISK!
[READ DISCLAMER.TXT!](https://joe.szalai.org/disclaimer/)
License: GNU General Public License v3

[![forthebadge](http://forthebadge.com/images/badges/built-by-developers.svg)](http://forthebadge.com) [![forthebadge](http://forthebadge.com/images/badges/for-you.svg)](http://forthebadge.com)
