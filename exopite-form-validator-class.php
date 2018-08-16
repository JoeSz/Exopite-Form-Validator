<?php

/**
 *
 * Form_Validator
 *
 * Inspired by: https://github.com/ASoares/PHP-Form-Validation
 *
 * A simple, flexible and easy to use PHP form validation class.
 * (uses a fluent interface )
 *
 * @link https://www.martinfowler.com/bliki/FluentInterface.html
 * @link https://en.wikipedia.org/wiki/Fluent_interface
 *
 * @author Joe Szalai
 * https://joe.szalai.org
 *
 * typical use:
 *
 *    $valid = new Form_Validator($_POST);
 *
 *    $valid->name('user_name')->required('You must choose a user name!')->alfa()->minSize(5);
 *
 *    $valid->name('user_email')->required()->email();
 *
 *    $valid->name('birthdate')->date('please enter date in YYYY-MM-DD format');
 *
 *    if ($valid->is_group_valid() )
 *        echo 'Validation Passed!';
 *
 * //////////////////////////////////////
 * OR
 *
 *     $valid = new Form_Validator($_POST);
 *
 *    if (  $valid->name('user_name')->required('You must choose a user name!')->alfa()->minSize(5)
 *            ->name('user_email')->required()->email()
 *            ->name('birthdate')->date('please enter date in YYYY-MM-DD format')
 *            ->is_group_valid() )
 *        echo 'Validation passed!';
 *
 *
 * //////////////////////////////////////////////////////////////////
 *    On HTML
 *  <form method="POST">
 *
 *        <input type="text"   name="email"
 *           value="<?php echo $valid->get_value('email'); ?>" />
 *        <span class="error">
 *        <?php echo $valid->get_error('email'); ?>
 *        </span>
 *        ...
 *        ...
 *
 * ///////////////////////////////////////////////////////////////////
 *  To create new validation rules!
 *
 *  #1 define default error message
 *  private static $error_myValidaton = 'my default error message';
 *
 *  #2 create new validation function
 *  function myValidation($param , $error_msg = null)
 *  {
 *  if ( $this->is_valid && (! empty( $this->current_obj->value ) ) )
 *  {
 *  //
 *  //code to check if validation pass
 *  //
 *  $this->is_valid = // true or false ;
 *  if (! $this->is_valid)
 *  $this->set_error_msg( $error_msg, self::$error_myValidation, $param);
 *  }
 *  return $this;
 *  }
 *
 * #3 use it
 * $Valid->name('testing')->myValidation(10, 'some error msg!');
 *
 *
 */

/**
 * ToDos:
 * - add sanitization (email, text, html, sql, textarea, trim)
 * - add is_password(strong|medium|normal) (https://github.com/azeemhassni/envalid/blob/master/src/Rules/Password.php)
 * - file? (https://github.com/azeemhassni/envalid)
 * - datetime (https://github.com/rlanvin/php-form/blob/master/src/rules.php)
 * - time (https://github.com/nilportugues/php-validator) (https://github.com/nilportugues/php-validator/blob/master/src/Validation/DateTime/DateTimeValidation.php)
 *   - is weekend
 *   - is after
 *   - is before
 *   - is monday,tuesday...
 *   - is today
 *   - is yesterday
 *   - is tomorrow
 *   - is next week
 *   - is last week
 *   - is x day before
 *   - is x day after
 *
 * Sanitize
 * - htmlentities (esc_html)
 * - strip_tags (esc_textarea)
 * - https://www.wordfence.com/learn/how-to-prevent-cross-site-scripting-attacks/
 * - https://code.tutsplus.com/articles/data-sanitization-and-validation-with-wordpress--wp-25536
 * text/textarea/html/url/email -> remove extra chars
 * esc_attr, esc_html, esc_textarea, esc_text, esc_url
 * ->esc( 'function' ) (html|text|textarea|url)
 */

/**
 * helper class for Form_Validator
 */
class Form_Validator_Obj
{

    public $value;
    public $error;
    public $name;

    function __construct( $value, $name ) {

        $this->value = $value;
        $this->name  = $name;
        $this->error = array();

    }

}


/**
 *
 */
class Form_Validator
{

    public $is_valid = true;
    public $is_group_valid = true;
    public $group_errors = array();
    public $valid_objs; //array of Form_Validator_Obj
    private $current_obj; //pointer to current Form_Validator_Obj , set by ->name()
    //default error messages
    private static $error_required = 'This field is required';
    private static $error_date = 'Please enter a valid datetime';
    private static $error_date_timezone = 'The supplied timezone [%s] is not supported.';
    private static $error_email = 'Please enter a valid email';
    private static $error_url = 'Please enter a valid url';
    private static $error_alpha = 'Only letters and numbers are permited';
    private static $error_text = 'Only letters are permited';
    private static $error_min_size = 'Please enter more than %s characters';
    private static $error_max_size = 'Please enter less than %s characters';
    private static $error_number_float = 'Only numbers are permitted';
    private static $error_number_integer = 'Only numbers are permitted';
    private static $error_boolean = 'Only true or false are permitted';
    private static $error_number_number = 'Only numbers are permitted';
    private static $error_number_max = 'Please enter a value lower than %s ';
    private static $error_number_min = 'Please enter a value greater than %s ';
    private static $error_one_of = 'Please choose one of " %s "';
    private static $error_equal = 'Fields did not match';
    private static $error_regex = 'Please choose a valid value';
    private static $error_is_in = 'Value is not in';
    private static $error_ip = 'Please enter a valid IP address';
    private static $error_callback = 'Please enter a valid value';
    // some regEx's
    private $pattern_url = '/^((http|ftp|https):\/\/)?www\..*.\.\w\w\w?(\/.*)?(\?.*)?$/'; //check...
    ////private $pattern_alfa = '/^[a-zA-Z0-9_\-\. ]+$/';
    // private $pattern_alfa = '/^(\d|\-|_|\.| |(\p{L}\p{M}*) )+$/u';
    private $pattern_alpha = '/^[a-zA-Z]+$/';
    private $pattern_text = '/^( |(\p{L}\p{M}*)+$/u';
    private $pattern_numberInteger = '/^[\+\-]?[0-9]+$/';
    private $pattern_numberFloat = '/^[\+\-]?[0-9\.]+$/';
    // private $pattern_date = '/^(19|20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/';


    /**
     *
     * @param Array $post  ($Key => $value ) array
     */
    function __construct( $post ) {

        foreach ( $post as $key => $value ) {
            $this->valid_objs[$key] = new Form_Validator_Obj( trim( $value ), $key );
        }

    }


    /**
     * Helper: returns true if last valiadtion passed , else false
     * @return Boolean
     */
    function is_valid() {

        return $this->is_valid;

    }

    /**
     * Helper: returns true if all validations passed , else false
     * @return Boolean
     */
    function is_group_valid() {
        return $this->is_group_valid;
    }


    /**
     * Flatten a multi-dimensional array and constructing concatenated keys for nested elements.
     * @param  array $array
     * @return array [array.subarray.subarray] => 'value'
     * @link source: http://stackoverflow.com/questions/9546181/flatten-multidimensional-array-concatenating-keys/9546215#9546215
     */
    public static function flat_concatenate_array( $array, $prefix = '' ) {

        //
        $result = array();
        foreach( $array as $key => $value ) {
            if( is_array( $value ) ) {
                $result = $result + self::flat_concatenate_array( $value, $prefix . $key . '.' );
            }
            else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }

    /**
     * Return's $name validation errors
     */
    function get_errors( $name = null, $glue = false ) {

        if ( isset( $name ) ) {

            if ( isset( $this->valid_objs[$name] ) ) {
                if ( $glue ) {
                    return implode( $glue, $this->valid_objs[$name]->error );
                } else {
                    return $this->valid_objs[$name]->error;
                }

            }

        } elseif ( is_array( $this->group_errors ) ) {

            $error_messages = array();

            $group_errors = $this->flat_concatenate_array( $this->group_errors );

            foreach ( $group_errors as $key => $value ) {
                $error_messages[] = $value;
            }

            if ( $glue ) {
                return implode( $glue, $error_messages );
            } else {
                return $error_messages;
            }

        }

        return array();
    }

    /**
     * Return's $name first validation error
     */
    function get_error( $name ) {

        if ( isset( $this->valid_objs[$name] ) ) {
            return $this->valid_objs[$name]->error[0];
        }

        return '';
    }

    /**
     * Returs $name value
     * @param string $name
     * @return string the value
     */
    function get_value( $name = null ) {

        if ( isset( $name ) && isset( $this->valid_objs[$name] ) ) {
            return $this->valid_objs[$name]->value;
        } else {
            return $this->current_obj->value;
        }

        return '';

    }


    /**
     * Used to set starting values on Form data
     * ex: $valid->name('user_name)->set_value($database->getUserName() );
     * @param string $value
     */
    function set_value( $value ) {

        $this->current_obj->value = $value;
        return $this;

    }


    /**
     *  used to set error messages out of the scope of Form_Validator
     *  ex: $valid->name('user_name')->set_error('The Name "Andre" is already taken , please try another')
     * @param string $error
     */
    function set_error( $error ) {
        $this->current_obj->error[] = $error;
        $this->is_group_valid = false;
        $this->is_valid = false;
        return $this;
    }


    /**
     * PRIVATE Helper to set error messages
     * @param string $error_msg custom error message
     * @param string $default  default error message
     * @param string $params   extra parameter to default error message
     */
    private function set_error_msg( $error_msg, $default, $params = null ) {

        $this->is_group_valid = false;

        if ( $error_msg == '' ) {
            $this->group_errors[$this->current_obj->name][] = sprintf( $default, $params );
            $this->current_obj->error[] = sprintf( $default, $params );
        } else {
            $this->group_errors[$this->current_obj->name][] = $error_msg;
            $this->current_obj->error[] = $error_msg;
        }

    }

    /**
     * Used to set a pointer for current validation object
     * if $name doesnt exits, it will be created with a empty value
     *      note:validation always pass on empy not required fields
     * @param string $name as in array($name => 'name value')
     * @return Form_Validator
     */
    function name( $name ) {

        if ( ! isset( $this->valid_objs[$name] ) ) {
            $this->valid_objs[$name] = new Form_Validator_Obj( '', $name );
        }

        $this->is_valid = true;

        $this->current_obj = &$this->valid_objs[$name];

        return $this;

    }

    ////////////////////////////////////
    ///     Validation Functions     ///
    ////////////////////////////////////

    /**
     * Note if field is required , then it must me called right after name!!
     * ex: $valid->name('user_name')->required()->text()->minSize(5);
     * @param string $error_msg
     * @return Form_Validator
     */
    function required( $error_msg = null ) {

        if ( $this->is_valid) {

            $this->is_valid = ( $this->current_obj->value != '' ) ? true : false;

            if ( ! $this->is_valid ) $this->set_error_msg( $error_msg, self::$error_required);
        }

        return $this;
    }

    public function validate_datetime( $date, $format = 'Y-m-d H:i:s' ) {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

/*
*   - is weekend
*   - is after
*   - is before
*   - is monday,tuesday...
*   - is today
*   - is/to yesterday
*   - is/to tomorrow
*   - is/to next month/week/day
*   - is/to last month/week/day
*   - is/to x month/week/day before
*   - is/to x month/week/day after
 */

    function convert_date( $format_in, $format_out, $timezone = null ) {

        if ( $format_in == 'en_US' || $format_in == 'us' ) $format_in = 'm/d/Y H:i:s';
        if ( $format_out == 'en_US' || $format_out == 'us' ) $format_out = 'm/d/Y H:i:s';
        if ( $format_in == 'de_DE' || $format_in == 'de' || $format_in == 'deutsch' ) $format_in = 'd.m.Y H:i:s';
        if ( $format_out == 'de_DE' || $format_out == 'de' || $format_out == 'deutsch' ) $format_out = 'd.m.Y H:i:s';
        if ( $format_in == 'hu_HU' || $format_in == 'hu' || $format_in == 'magyar' ) $format_in = 'Y.m.d H:i:s';
        if ( $format_out == 'hu_HU' || $format_out == 'hu' || $format_out == 'magyar' ) $format_out = 'Y.m.d H:i:s';
        if ( $format_in == 'iso' ) $format_in = 'Y-m-d H:i:s';
        if ( $format_out == 'iso' ) $format_out = 'Y-m-d H:i:s';
        if ( $format_in == 'en_US_date' || $format_in == 'us_date' ) $format_in = 'm/d/Y';
        if ( $format_out == 'en_US_date' || $format_out == 'us_date' ) $format_out = 'm/d/Y';
        if ( $format_in == 'de_DE_date' || $format_in == 'de_date' || $format_in == 'deutsch_datum' ) $format_in = 'd.m.Y';
        if ( $format_out == 'de_DE_date' || $format_out == 'de_date' || $format_out == 'deutsch_datum' ) $format_out = 'd.m.Y';
        if ( $format_in == 'hu_HU_date' || $format_in == 'hu_date' || $format_in == 'magyar_datum' ) $format_in = 'Y.m.d';
        if ( $format_out == 'hu_HU_date' || $format_out == 'hu_date' || $format_out == 'magyar_datum' ) $format_out = 'Y.m.d';
        if ( $format_in == 'iso_date' ) $format_in = 'Y-m-d';
        if ( $format_out == 'iso_date' ) $format_out = 'Y-m-d';
        if ( $format_in == 'time' ) $format_in = 'H:i:s';
        if ( $format_out == 'time' ) $format_out = 'H:i:s';

        if ( ! ( $timezone instanceof DateTimeZone) && ! is_null( $timezone ) ) {

            try {

                $timezone = new DateTimeZone($timezone);

            } catch (Exception $error) {

                $this->current_obj->value = null;
                $this->set_error_msg( $error_msg, self::$error_date_timezone, $timezone);

            }

        }

        if ( ! empty( $this->current_obj->value ) ) {

            if ( $this->validate_datetime( $this->current_obj->value, $format_in ) ) {

                $date = DateTime::createFromFormat( $format_in, $this->current_obj->value, $timezone );

                if ( $date && isset( $format_out ) && $date->format( $format_out ) ) {

                    $this->current_obj->value = $date->format( $format_out );

                }

            } else {
                $this->set_error_msg( $error_msg, self::$error_date);
            }

        }

        return $this;

    }

    /**
     *  validates a Date in yyyy-mm-dd format
     * @param string $error_msg
     * @return Form_Validator
     */
    function is_datetime( $format_in = 'Y-m-d H:i:s', $error_msg = null ) {

        switch ( $format ) {
            case 'en_US':
            case 'us':
                $format = 'm/d/Y H:i:s';
                break;
            case 'en_US_date':
            case 'us_date':
                $format = 'm/d/Y';
                break;
            case 'de':
            case 'de_DE':
            case 'deutsch':
                $format = 'd.m.Y H:i:s';
                break;
            case 'de_date':
            case 'de_DE_date':
            case 'deutsch_datum':
                $format = 'd.m.Y';
                break;
            case 'hu':
            case 'hu_HU':
            case 'magyar':
                $format = 'Y.m.d H:i:s';
                break;
            case 'hu_date':
            case 'hu_HU_date':
            case 'magyar_datum':
                $format = 'Y.m.d';
                break;
            case 'iso_date':
                $format = 'Y-m-d';
                break;
            case 'time':
                $format = 'H:i:s';
                break;
        }

        /**
         * format or country code as en_time, en_datetime en_date, , us, de, hu
         */
        if ( ! empty( $this->current_obj->value ) ) {

            $this->is_valid = $this->validate_datetime( $this->current_obj->value, $format );

            if ( ! $this->is_valid ) $this->set_error_msg( $error_msg, self::$error_date);

        }

        return $this;
    }

    /**
     * Check if value is in input.
     * Input can be:
     * - an another element name
     * - a string
     * - an array
     */
    function is_in( $input, $error_msg = null ) {

        if ( ! empty( $this->current_obj->value ) ) {

            if ( is_string( $input ) && isset( $this->valid_objs[$input] ) ) {
            // if ( isset( $this->valid_objs[$input] ) ) {
                $input = $this->valid_objs[$input]->value;
            }

            if ( is_array( $this->current_obj->value ) && ! is_array( $input ) ) {

                $this->is_valid = ( $this->find_key_value( $this->current_obj->value, $input, true ) );

            } elseif ( is_array( $this->current_obj->value ) && is_array( $input ) ) {

                $this->is_valid = count( array_intersect( $input, $this->current_obj->value ) ) == count( $input );

            } elseif ( ! is_array( $this->current_obj->value ) && is_array( $input ) ) {

                // for array keys
                if ( $this->find_key_value( $input, $this->current_obj->value, true ) ) {
                    $this->is_valid = true;
                } else {

                    $this->is_valid = ( $this->in_array_r( $this->current_obj->value, $input ) );

                }

            } else {

                $this->is_valid = ( strstr( $this->current_obj->value, $input ) !== false );

            }

            if ( ! $this->is_valid ) $this->set_error_msg( $error_msg, self::$error_is_in );

        }

        return $this;

    }

    /**
     * in_array() and multidimensional array
     * @param  string  $needle
     * @param  array   $haystack
     * @param  boolean $strict
     * @return boolean
     *
     * @link https://stackoverflow.com/questions/4128323/in-array-and-multidimensional-array/4128377#4128377
     */
    function in_array_r( $needle, $haystack, $strict = false ) {
        foreach ( $haystack as $item ) {
            if ( ( $strict ? $item === $needle : $item == $needle) || ( is_array( $item ) && in_array_r( $needle, $item, $strict ) ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve value from array if specific array key exists in multidimensional array.
     *
     * Based on:
     * @link https://stackoverflow.com/questions/19420715/check-if-specific-array-key-exists-in-multidimensional-array-php/19421060#19421060
     */
    function find_key_value( $array, $needle, $isset = false ) {

        // check if it's even an array
        if ( ! is_array( $array ) ) return false;

        // key exists
        if ( array_key_exists( $needle, $array ) ) return ( $isset ) ? true : $array[$needle];

        // key isn't in this array, go deeper
        foreach( $array as $key => $val ) {

            // return true if it's found
            if ( self::find_key_value( $val, $needle ) ) return ( $isset ) ? true : self::find_key_value( $val, $needle );

        }

        return false;
    }

   /**
     * validates a URL address
     * @param string $error_msg
     * @return Form_Validator
     *
     * Using FILTER_VALIDATE_URL is not the best option:
     * @link https://d-mueller.de/blog/why-url-validation-with-filter_var-might-not-be-a-good-idea/
     */
    function is_url( $error_msg = null ) {

        if ( ! empty( $this->current_obj->value ) ) {

            $this->is_valid = ( preg_match( $this->pattern_url, $this->current_obj->value ) ) ? true : false;
            if ( ! $this->is_valid ) $this->set_error_msg( $error_msg, self::$error_url);

        }

        return $this;
    }


    /**
     * ex: ->regex('/^[^<>]+$/', 'ERROR:  < and > arent valid characters')
     * @param string $regex a regular expresion '/regex/'
     * @param string $error_msg
     * @return Form_Validator
     */
    function regex( $regex, $error_msg = null ) {

        if ( ! empty( $this->current_obj->value ) ) {

            $this->is_valid = ( preg_match( $regex, $this->current_obj->value ) ) ? true : false;
            if ( ! $this->is_valid ) $this->set_error_msg( $error_msg, self::$error_regex);

        }

        return $this;
    }

    /**
     * Ex: ->is_one_of('blue:red:green' , 'only blue , red and green permited')
     * *case insensitive*
     * @param string $items ex: 'blue:red:green'
     * @param string $error_msg
     * @return Form_Validator
     */
    function is_one_of( $items, $error_msg = null ) {
        if ( ! empty( $this->current_obj->value ) ) {

            $item = explode( ':', strtolower( $items ) );
            $result = array_intersect( $item, array( strtolower( $this->current_obj->value ) ) );
            $this->is_valid = ( ! empty( $result ) );

            if ( ! $this->is_valid ) {
                $itemsList = str_replace( ':', ' / ', $items );
                $this->set_error_msg( $error_msg, self::$error_one_of, $itemsList );
            }

        }

        return $this;
    }

    /**
     * Only allows A-Z a-Z and space
     * @param string $error_msg
     * @return Form_Validator
     */
    function is_text( $error_msg = null ) {

        if ( ! empty( $this->current_obj->value ) ) {

            $this->is_valid = ( preg_match( $this->pattern_text, $this->current_obj->value ) ) ? true : false;
            if ( ! $this->is_valid ) $this->set_error_msg( $error_msg, self::$error_text);

        }

        return $this;
    }


    /**
     * Only allows A-Z a-z 0-9 space and ( - . _ )
     * @param string $error_msg
     * @return Form_Validator
     */
    function is_alpha( $error_msg = null ) {

        if ( ! empty( $this->current_obj->value ) )  {

            $this->is_valid = ( preg_match( $this->pattern_alpha, $this->current_obj->value ) ) ? true : false;
            if ( ! $this->is_valid ) $this->set_error_msg( $error_msg, self::$error_alpha);

        }

        return $this;
    }

    /**
     * @param int $size the maximum string size
     * @param string $error_msg
     * @return Form_Validator
     */
    function is_max( $size, $error_msg = null ) {

        if ( ! empty( $this->current_obj->value ) ) {

            if ( is_array( $this->current_obj->value ) ) {
                $this->is_valid = ( count( $this->current_obj->value ) <= $size );
            } else {
                $this->is_valid = ( strlen( $this->current_obj->value ) <= $size );
            }

            if ( ! $this->is_valid ) $this->set_error_msg( $error_msg, self::$error_max_size, $size);

        }

        return $this;
    }

    /**
     * @param int $size the minimum string size
     * @param string $error_msg
     * @return Form_Validator
     */
    function is_min( $size, $error_msg = null ) {

        if ( ! empty( $this->current_obj->value ) ) {

            if ( is_array( $this->current_obj->value ) ) {
                $this->is_valid = ( count( $this->current_obj->value ) >= $size );
            } else {
                $this->is_valid = ( strlen( $this->current_obj->value ) >= $size );
            }

            if ( ! $this->is_valid ) $this->set_error_msg( $error_msg, self::$error_min_size, $size);

        }

        return $this;
    }

    /**
     * Check if two arrays are equal
     *
     * @link https://stackoverflow.com/questions/5678959/php-check-if-two-arrays-are-equal/39532595#39532595
     */
    public function is_equal_arrays( $array1, $array2 ) {

        array_multisort( $array1 );
        array_multisort( $array2 );
        return ( serialize( $array1 ) === serialize( $array2 ) );

    }

    /**
     * ex: ->name('password')->is_equal('passwordConfirm' , 'passwords didnt match')
     * Check element for
     * - an another element value -> element value is same as the other element value
     * - a string -> two strings are the same
     * - an array -> two arrays are the same
     * - a number -> length for the array or string
     * @param int/string/array/element name  $to_compare
     * @param string  $error_msg
     * @return Form_Validator
     */
    function is_equal( $to_compare, $error_msg = null ) {

        if ( ! empty( $this->current_obj->value ) ) {

            if ( isset( $this->valid_objs[$to_compare] ) ) {
                $to_compare = $this->valid_objs[$to_compare]->value;
            }

            /**
             * if it is an array
             */
            if ( is_array( $this->current_obj->value ) ) {

                /**
                 * if "to_compare" is int, then check the length
                 * overwise  check if they are equal
                 */
                if ( is_int( $to_compare ) ) {
                    $this->is_valid = ( count( $this->current_obj->value ) === $to_compare );
                } elseif ( is_array( $to_compare ) ) {
                    $this->is_valid = ( is_equal_arrays( $this->current_obj->value, $to_compare ) );
                } else {
                    $this->is_valid = false;
                }

            } else {

                if ( is_int( $to_compare ) && ! is_int( $this->current_obj->value ) ) {
                    $this->is_valid = ( strlen( $this->current_obj->value ) === $to_compare );
                } else {
                    $this->is_valid = ( $to_compare == $this->current_obj->value );
                }

            }

            if ( ! $this->is_valid ) $this->set_error_msg( $error_msg, self::$error_equal);

        }

        return $this;
    }

    /**
     * checks if its a float ( +  -  . ) permited
     * @param string $error_msg
     * @return Form_Validator
     */
    function is_float( $error_msg = null ) {

        if ( ! empty( $this->current_obj->value ) ) {

            $this->is_valid = ( is_float( $this->current_obj->value ) ) ? true : false;
            if ( ! $this->is_valid ) $this->set_error_msg( $error_msg, self::$error_number_float);

        }

        return $this;
    }


    /**
     * checks if its a integer ( +  - ) permited
     * @param string $error_msg
     * @return Form_Validator
     */
    function is_integer( $error_msg = null ) {

        if ( ! empty( $this->current_obj->value ) ) {

            $this->is_valid = ( is_int( $this->current_obj->value ) ) ? true : false;
            if ( ! $this->is_valid ) $this->set_error_msg( $error_msg, self::$error_number_integer);

        }

        return $this;
    }

    /**
     * validates a boolean
     * @param string $error_msg
     * @return Form_Validator
     */
    function is_boolean( $error_msg = null ) {

        if ( ! empty( $this->current_obj->value ) ) {

            $this->is_valid = ( filter_var( $this->current_obj->value, FILTER_VALIDATE_BOOLEAN ) );

            if ( ! $this->is_valid ) $this->set_error_msg( $error_msg, self::$error_boolean );
        }

        return $this;
    }

    /**
     * checks if its a number permited
     * @param string $error_msg
     * @return Form_Validator
     */
    function is_number( $error_msg = null ) {

        //is_numeric

        if ( ! empty( $this->current_obj->value ) ) {

            $this->is_valid = ( is_numeric( $this->current_obj->value ) ) ? true : false;
            if ( ! $this->is_valid ) $this->set_error_msg( $error_msg, self::$error_number_integer );

        }

        return $this;

    }

    /**
     * Conditional checking
     *
     * If condition does not met, stop further checking without an error.
     *
     * @param  string $element   element name to check
     * @param  string $value     element value to check
     * @param  string $condition condition (equal, not_equal, smaller, bigger, exist, ==, !=, <>, >=, <=)
     * @return Form_Validator
     */
    function is( $element, $value = '', $condition = 'equal' ) {

        if ( ! empty( $element ) ) {

            $element = ( is_array( $element ) ) ? $element : array( $element => $value );

            foreach ( $element as $key => $value ) {

                if ( $value == 'exist' || $condition == 'exist' ) {
                    $condition = ( isset( $this->valid_objs[$key] ) );
                } else {

                    switch ( $condition ) {
                        case 'equal':
                        case '==':
                            $condition = ( isset( $this->valid_objs[$key] ) && $this->valid_objs[$key]->value == $value );
                            break;
                        case 'not_equal':
                        case '!=':
                        case '<>':
                            $condition = ( isset( $this->valid_objs[$key] ) && $this->valid_objs[$key]->value != $value );
                            break;
                        case 'smaller':
                        case '<':
                            $condition = ( isset( $this->valid_objs[$key] ) && $this->valid_objs[$key]->value < $value );
                            break;
                        case 'bigger':
                        case '>':
                            $condition = ( isset( $this->valid_objs[$key] ) && $this->valid_objs[$key]->value > $value );
                            break;
                        case 'smaller':
                        case '<':
                            $condition = ( isset( $this->valid_objs[$key] ) && $this->valid_objs[$key]->value < $value );
                            break;
                        case '<=':
                            $condition = ( isset( $this->valid_objs[$key] ) && $this->valid_objs[$key]->value <= $value );
                            break;
                        case '>=':
                            $condition = ( isset( $this->valid_objs[$key] ) && $this->valid_objs[$key]->value >= $value );
                            break;

                    }

                }

                // If element not the same, prevent further checking
                if ( ! $condition ) {

                    $this->current_obj->value = '';
                    break;

                }

            }

        }

        return $this;

    }

    /**
     * validates an email address
     * @param string $error_msg
     * @return Form_Validator
     */
    function is_email( $error_msg = null ) {

        if ( ! empty( $this->current_obj->value ) ) {

            $this->is_valid = ( filter_var( $this->current_obj->value, FILTER_VALIDATE_EMAIL ) );
            if ( ! $this->is_valid ) $this->set_error_msg( $error_msg, self::$error_email);
        }

        return $this;
    }

    /**
     * user callback
     * ->callback( 'function_name', $args )
     * function_name( $args, object ) {}
     * @param string $error_msg
     * @return Form_Validator
     */
    function callback( $callback = null, $args = array(), $error_msg = null ) {

        if ( ! empty( $this->current_obj->value ) ) {

            if ( is_callable( $callback ) ) {

                $this->is_valid = call_user_func_array( $callback, array( $args, &$this->current_obj ) );

            } else {

                $this->is_valid = false;

            }

            if ( ! $this->is_valid ) $this->set_error_msg( $error_msg, self::$error_callback );

        }

        return $this;
    }

    /**
     * ->is_char( 'alpha,number,...' )
     * regex: https://www.regular-expressions.info/posixbrackets.html
     */
    function is_chars( $allowed, $error_msg = null ) {

        $regex = '';

        $allowed = explode( ',', $allowed );

        foreach ( $allowed as $allow ) {

            switch ( $allow ) {
                case 'alpha':
                    $regex .= '[:alpha:]';
                    break;
                case 'number':
                    $regex .= '[:digit:]';
                    break;
                case 'dash':
                    $regex .= '\-_';
                    break;
                case 'space':
                    $regex .= ' ';
                    break;
                case 'special':
                    $regex .= '[:punct:]';
                    break;
                case 'word':
                    $regex .= '[:word:]';
                    break;
                case 'lowercase':
                    $regex .= '[:lower:]';
                    break;
                case 'uppercase':
                    $regex .= '[:upper:]';
                    break;
                case 'hex':
                    $regex .= '[:xdigit:]';
                    break;
                default:
                    $regex .= '[:alnum:]';
                    break;
            }

        }

        $regex = '/^([' . $regex . '])+$/';

        return $this->regex( $regex, $error_msg );
        // return $this;

    }

    /**
     * validates an ip address
     * @param string $error_msg
     * @return Form_Validator
     */
    function is_ip( $flag = 'ip4', $error_msg = null ) {

        if ( ! empty( $this->current_obj->value ) ) {

            switch ( $flag ) {
                case 'ip4':
                    $flag = FILTER_FLAG_IPV4;
                    break;
                case 'ip6':
                    $flag = FILTER_FLAG_IPV6;
                    break;

            }

            $this->is_valid = ( filter_var( $this->current_obj->value, FILTER_VALIDATE_IP, $flag ) );
            if ( ! $this->is_valid ) $this->set_error_msg( $error_msg, self::$error_ip);
        }

        return $this;
    }

    /**
     * Escaping for HTML attributes.
     * WordPress function.
     * @return Form_Validator
     * @link https://developer.wordpress.org/reference/functions/esc_attr/
     */
    function esc_attr() {

        // Check if running under WordPress
        if ( ! empty( $this->current_obj->value ) && defined('ABSPATH') ) {

            if ( defined('ABSPATH') ) {
                $this->current_obj->value = esc_attr( $this->current_obj->value );
            } else {
                $this->current_obj->value = htmlentities( $this->current_obj->value, ENT_QUOTES, "utf-8", false );
            }

        }

        return $this;
    }

    /**
     * Escape single quotes, htmlspecialchar ” &, and fix line endings.
     * WordPress function.
     * @return Form_Validator
     * @link https://developer.wordpress.org/reference/functions/esc_js/
     */
    function esc_js() {

        // Check if running under WordPress
        if ( ! empty( $this->current_obj->value ) && defined('ABSPATH') ) {

            $this->current_obj->value = esc_js( $this->current_obj->value );

        }

        return $this;
    }

    /**
     * Escaping for HTML blocks.
     *
     * wp_kses:
     * This function makes sure that only the allowed HTML element names, attribute names and
     * attribute values plus only sane HTML entities will occur in $string. You have to remove
     * any slashes from PHP's magic quotes before you call this function.
     *
     * WordPress function.
     * @return Form_Validator
     * @link https://codex.wordpress.org/Function_Reference/esc_html
     * @link https://codex.wordpress.org/Function_Reference/wp_kses
     */
    function esc_html( $allowed_html = '', $allowed_protocols = '' ) {

        // Check if running under WordPress
        if ( ! empty( $this->current_obj->value ) ) {

            if ( defined('ABSPATH') ) {
                if ( ! empty( $allowed_html ) ||! empty( $allowed_protocols ) ) {
                    $this->current_obj->value = wp_kses( $this->current_obj->value, $allowed_html, $allowed_protocols );
                } else {
                    $this->current_obj->value = esc_html( $this->current_obj->value );
                }
            } else {
                $this->current_obj->value = htmlspecialchars( $this->current_obj->value, ENT_QUOTES, "utf-8", false );
            }

        }

        return $this;
    }

    /**
     * Encodes text for use inside a <textarea> element.
     * WordPress function.
     * @return Form_Validator
     * @link https://codex.wordpress.org/Function_Reference/esc_textarea
     * @link https://stackoverflow.com/questions/20444042/wordpress-how-to-sanitize-multi-line-text-from-a-textarea-without-losing-line/34654458#34654458
     */
    function esc_textarea() {

        // Check if running under WordPress
        if ( ! empty( $this->current_obj->value ) ) {

            if ( defined('ABSPATH') ) {
                $this->current_obj->value = stripslashes( esc_textarea( $this->current_obj->value ) );
            } else {
                $this->current_obj->value = strip_tags( $this->current_obj->value );
            }

        }

        return $this;
    }

    /**
     * Always use esc_url when sanitizing URLs (in text nodes, attribute nodes or anywhere else).
     * Rejects URLs that do not have one of the provided whitelisted protocols
     * (defaulting to http, https, ftp, ftps, mailto, news, irc, gopher, nntp, feed, and telnet),
     * eliminates invalid characters, and removes dangerous characters. This function encodes
     * characters as HTML entities: use it when generating an (X)HTML or XML document. Encodes
     * ampersands (&) and single quotes (') as numeric entity references (&#038, &#039).
     *
     * If the URL appears to be an absolute link that does not contain a scheme, prepends http://.
     * Please note that relative urls (/my-url/parameter2/), as well as anchors (#myanchor) and
     * parameter items (?myparam=yes) are also allowed and filtered as a special case, without
     * prepending the default protocol to the filtered url.
     *
     * WordPress function.
     * @return Form_Validator
     * @link https://codex.wordpress.org/Function_Reference/esc_url
     */
    function esc_url() {

        // Check if running under WordPress
        if ( ! empty( $this->current_obj->value ) && defined('ABSPATH') ) {

            $this->current_obj->value = esc_url( $this->current_obj->value );

        }

        return $this;
    }

    /**
     * Sanitizes a string from user input or from the database.
     * WordPress function.
     * @return Form_Validator
     * @link https://developer.wordpress.org/reference/functions/sanitize_text_field/
     */
    function sanitize_text_field() {

        // Check if running under WordPress
        if ( ! empty( $this->current_obj->value ) && defined('ABSPATH') ) {

            if ( defined('ABSPATH') ) {
                $this->current_obj->value = sanitize_text_field( $this->current_obj->value );
            } else {
                $this->current_obj->value = preg_replace( "/\r|\n/", "", strip_tags( $this->current_obj->value ) );
            }

        }

        return $this;
    }

    /**
     * Sanitizes title or use fallback title.
     *
     * Specifically, HTML and PHP tags are stripped, and (in a 'save' context) accents are removed
     * (accented characters are replaced with non-accented equivalents). Further filtering can be
     * added via the plugin API by hooking the sanitize_title filter.
     * If $title is empty and $fallback_title is set, the latter will be used.
     *
     * Despite the name of this function, the returned value is intended to be suitable for use in a URL,
     * not as a human-readable title.
     *
     * WordPress function.
     * @return Form_Validator
     * @link https://codex.wordpress.org/Function_Reference/sanitize_title
     */
    function sanitize_title() {

        // Check if running under WordPress
        if ( ! empty( $this->current_obj->value ) ) {

            if ( defined('ABSPATH') ) {
                $this->current_obj->value = sanitize_title( $this->current_obj->value );
            } else {
                $this->current_obj->value = $this->seo_url( $this->current_obj->value );
            }



        }

        return $this;
    }

    /**
     * This function will create an SEO friendly string-
     *
     * @param  [type] $string
     * @return [type] $string
     *
     * @link https://stackoverflow.com/questions/11330480/strip-php-variable-replace-white-spaces-with-dashes/11330527#11330527
     */
    public function seo_url( $string ) {

        //Remove all HTML tags
        $string = strip_tags( $string );
        //Lower case everything
        $string = strtolower( $string );
        //Make alphanumeric (removes all other characters)
        $string = preg_replace("/[^a-z0-9_\s-]/", "", $string );
        //Clean up multiple dashes or whitespaces
        $string = preg_replace( "/[\s-]+/", " ", $string );
        //Convert whitespaces to dash (leave underscores)
        $string = preg_replace( "/[\s]/", "-", $string );

        return $string;
    }

    /**
     * Strips out all characters that are not allowable in an email.
     * @return Form_Validator
     * @link https://developer.wordpress.org/reference/functions/sanitize_email/
     * @link http://php.net/manual/de/filter.filters.sanitize.php
     */
    function sanitize_email() {

        // Check if running under WordPress
        if ( ! empty( $this->current_obj->value ) ) {

            if ( defined('ABSPATH') ) {
                $this->current_obj->value = sanitize_email( $this->current_obj->value );
            } else {
                $this->current_obj->value = filter_var( $this->current_obj->value, FILTER_SANITIZE_EMAIL );
            }

        }

        return $this;
    }

    /**
     * Sanitizes a html classname to ensure it only contains valid characters.
     *
     * Strips the string down to A-Z,a-z,0-9,_,-. If this results in an empty string,
     * then the function will return the alternative value supplied. After sanitize_html_class()
     * has done its work, it passes the sanitized class name through the sanitize_html_class filter.
     *
     * WordPress function.
     * @return Form_Validator
     * @link https://codex.wordpress.org/Function_Reference/sanitize_html_class
     */
    function sanitize_html_class() {

        // Check if running under WordPress
        if ( defined('ABSPATH') ) {
            $this->current_obj->value = sanitize_html_class( $this->current_obj->value );
        } else {
            $this->current_obj->value = $this->seo_url( $this->current_obj->value );
        }

        return $this;
    }

    /**
     * The FILTER_SANITIZE_URL filter removes all illegal URL characters from a string.
     * @return Form_Validator
     * @link http://php.net/manual/de/filter.filters.sanitize.php
     */
    function sanitize_url() {

        // Check if running under WordPress
        if ( ! empty( $this->current_obj->value ) ) {

            $this->current_obj->value = filter_var( $this->current_obj->value, FILTER_SANITIZE_URL );

        }

        return $this;
    }

    /**
     * Make a string lowercase.
     * @return Form_Validator
     * @link http://php.net/manual/en/function.strtolower.php
     */
    function to_lower() {

        if ( ! empty( $this->current_obj->value ) ) {

            $this->current_obj->value = strtolower( $this->current_obj->value );

        }

        return $this;
    }

    /**
     * Make a string uppercase.
     * @return Form_Validator
     * @link http://php.net/manual/de/function.strtoupper.php
     */
    function to_upper() {

        if ( ! empty( $this->current_obj->value ) ) {

            $this->current_obj->value = strtoupper( $this->current_obj->value );

        }

        return $this;
    }

    /**
     * Uppercase the first character of each word in a string.
     * @return Form_Validator
     * @link http://php.net/manual/en/function.ucwords.php
     */
    function to_camel_case() {

        if ( ! empty( $this->current_obj->value ) ) {

            $this->current_obj->value = ucwords( $this->current_obj->value );

        }

        return $this;
    }

    /**
     * Make a string's first character uppercase.
     * @return Form_Validator
     * @link http://php.net/manual/en/function.ucwords.php
     */
    function to_capital() {

        if ( ! empty( $this->current_obj->value ) ) {

            $this->current_obj->value = ucfirst( $this->current_obj->value );

        }

        return $this;
    }

}
