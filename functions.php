<?php

/**
 * PunBB Dice Roller extension functions file
 *
 * @copyright Copyright (C) 2009 Raul Ferriz
 * @license http://www.gnu.org/licenses/gpl.html GPL version 3
 * @package pun_dice
 *
 * Changelog:
 *  v0.1 Initial release
 *  v0.3 Added conditions
 *       First steps in multilanguage support
 */

if (!defined('FORUM')) die();

define('DR_FALSE_STRING', ' Falso! ');
define('DR_TRUE_STRING', ' Cierto! ');
define('DR_INVALID', ' Expresi&oacute;n no v&aacute;lida ');
define('DR_INVALID_EXPRESSION', ' Expresi&oacute;n no v&aacute;lida ');

if (!function_exists('dr_parse'))
{
  function dr_parse($text)
  {
    global $dr_active_post;
    srand($dr_active_post);

    // If the message contains a dice tag we have to split it up (text within [dice][/dice] shouldn't be touched)
    if (strpos($text, '[dice]') !== false && strpos($text, '[/dice]') !== false)
    {
      list($inside, $outside) = split_text($text, '[dice]', '[/dice]', $errors);
      $text = implode("\0", $outside);
    }

    if (isset($inside))
    {

      for($i = 0; $i < count($inside); $i++)
      {

        // Split in expressions
        $expressions = dr_split_expression($inside[$i]);
        $inside[$i] = '(' . $inside[$i] . ') : ';

        if (isset($expressions))
        {

          $to_eval = '';

          foreach ($expressions as $key => $expression)
          {
            $tmp_result = dr_parse_expression($expression);
            $inside[$i] .= $tmp_result;
            $to_eval .= $tmp_result;
          }

          // We must convert some simbols for evalue them
          $to_eval = str_replace (
                                   array("&gt;",  "&lt;"),
                                   array(">", "<"),
                                   $to_eval);

          // We can only have one operator of any type
          if ((substr_count ($to_eval, '<=') > 1) or
              (substr_count ($to_eval, '>=') > 1) or
              (substr_count ($to_eval, '<') > 1) or
              (substr_count ($to_eval, '>') > 1) or
              (substr_count ($to_eval, '=') > 1))
          {
            $inside[$i] .= " : " . DR_INVALID_EXPRESSION;
          }
          else
          // Evaluate result
          if ((strpos($to_eval, '<') !== false) or
              (strpos($to_eval, '>') !== false) or
              (strpos($to_eval, '=') !== false))
          {
            $to_eval = "\$inside[\$i] .= \" : \" . ((" . $to_eval . ") === true ? \"" . DR_TRUE_STRING ."\" : \"" . DR_FALSE_STRING . "\");";
            echo eval($to_eval);
          }
          else
          {
            $to_eval = "\$inside[\$i] .= \" = \" . (" . $to_eval . ");";
            eval($to_eval);
          }
        }
        else
        {
          $inside[$i] .= DR_INVALID_EXPRESSION;
        }
      }
    }

    // If we split up the message before we have to concatenate it together again (dice tags)
    if (isset($inside))
    {
      $outside = explode("\0", $text);
      $text = '';

      $num_tokens = count($outside);

      for ($i = 0; $i < $num_tokens; ++$i)
      {
        $text .= $outside[$i];
        if (isset($inside[$i]))
          $text .= '[dice]'. $inside[$i] . '[/dice]';
      }
    }

    return $text;

  }
}

if (!function_exists('dr_split_expression'))
{
  /**
   * This functions split $text in chunk of operators and expressions
   */
  function dr_split_expression($text)
  {
    $result = array ();
    $string = $text;
    $op_position = false;
    $operator = '';
    do
    {
      $op_position = strpos($string, '&gt;=');
      if ($op_position !== false)
        $operator = '&gt;=';
      else
      {
        $op_position = strpos($string, '&lt;=');
        if ($op_position !== false)
          $operator = '&lt;=';
        else
        {
          $op_position = strpos($string, '=');
          if ($op_position !== false)
            $operator = '=';
            else
          {
            $op_position = strpos($string, '&gt;');
            if ($op_position !== false)
              $operator = '&gt;';
            else
            {
              $op_position = strpos($string, '&lt;');
              if ($op_position !== false)
                $operator = '&lt;';
            }
          }
        }
      }
      if ($op_position !== false)
      {
        $result[] = substr($string, 0, $op_position);
        $result[] = $operator;
        $string = substr($string, strlen($result[count($result) - 2]) +  strlen($operator));
      }
      else
      {
        $result[] = $string;
      }
    }
    while ($op_position !== false);
    return $result;
  }
}

if (!function_exists('dr_parse_expression'))
{
  function dr_parse_expression($expression) {
    $result = $expression;
    if (strpos ($expression, 'd') !== false)
    {
      $result = '';
      $throw = explode('d', $expression);
      if (isset($throw))
      {
        $sum_result = 0;
        $num_dices = $throw[0];
        for ($j = 0; $j < $num_dices; $j++)
        {
          $dice_result = roll_dice((int)$throw[1]);
          $sum_result += $dice_result;
          if ($j > 0)
            $result .= ' + ';
          $result .= $dice_result;
        }
      }
    }
    return $result;
  }
}


if (!function_exists('roll_dice'))
{
  function roll_dice($faces) {
    return rand(1, $faces);
  }
}
?>
