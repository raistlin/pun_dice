<?php

/**
 * PunBB Dice Roller extension functions file
 *
 * @copyright (C) 2009 Raul Ferriz
 * @license http://www.gnu.org/licenses/gpl.html GPL version 3
 * @package pun_dice
 *
 * Changelog:
 *  v0.4
 *	      Added support for objecttive dice (do)
 *  v0.3.4
 *	      Added support for multiplication and division
 *  v0.3.3
 *	      Added support for parentesis
 *  v0.3.2
 *       Added support for quoting rolling dices
 *  v0.3.1
 *	      Fixed bug with interaction agains bbcode [code][/code]
 *  v0.3 Added conditions
 *       First steps in multilanguage support
 *  v0.1 Initial release
 */

if (!defined('FORUM')) die();

if (!function_exists('dr_parse'))
{
  function dr_parse($input_text, $is_quote = false)
  {
    global $dr_active_post;
    srand($dr_active_post);

    // If the message contains a code tag we have to split it up (text within [code][/code] shouldn't be touched)
    if (strpos($input_text, '[code]') !== false && strpos($input_text, '[/code]') !== false)
    {
      list($inside_code, $outside_code) = split_text($input_text, '[code]', '[/code]', $errors);
      $input_text = implode("\0", $outside_code);
    }
    else
    {
      $outside_code = array ($input_text);
    }

    foreach($outside_code as $key => $text)
    {
      // If the message contains a dice tag we have to split it up (text within [dice][/dice] shouldn't be touched)
      if (strpos($text, '[dice]') !== false && strpos($text, '[/dice]') !== false)
      {
        list($inside, $outside) = split_text($text, '[dice]', '[/dice]', $errors);
      }

      if (isset($inside))
      {
        $num_tokens = count ($inside);

        for($i = 0; $i < $num_tokens; $i++)
        {

          $is_quote = (stripos($inside[$i], '>') !== false) or (stripos($inside[$i], '<') !== false);
          if ($is_quote)
          {
            // We must convert some simbols, only when when we come for quoting
            $inside[$i] = str_replace (
                                        array('>', '<'),
                                        array('&gt;',  '&lt;'),
                                        $inside[$i]);
          }

          // Split in expressions
          $expressions = dr_split_expression($inside[$i]);
          $inside[$i] = '(' . $inside[$i] . ') : ';

          if (isset($expressions))
          {

            $to_eval = '';

            foreach ($expressions as $expression)
            {
              $tmp_result = dr_parse_expression($expression);
              $inside[$i] .= $tmp_result;
              $to_eval .= $tmp_result;
            }

            // We must convert some simbols for evalue them
            $to_eval = str_replace ( array("&gt;",  "&lt;"),
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
            {
              // Evaluate the result
              if ((strpos($to_eval, '<') !== false) or
                  (strpos($to_eval, '>') !== false) or
                  (strpos($to_eval, '=') !== false))
              {
                eval('$inside[$i] .= " : " . ((' . $to_eval . ') === true ? DR_TRUE_STRING : DR_FALSE_STRING );');
              }
              else
              {
                eval('$inside[$i] .= " = " . (' . $to_eval . ');');
              }
            }
          }
          else
          {
            $inside[$i] .= DR_INVALID_EXPRESSION;
          }

          if ($is_quote)
          {
            // We must restore some simbols, only when when we come for quoting
            $inside[$i] = str_replace (
                                        array('&gt;',  '&lt;'),
                                        array('>', '<'),
                                        $inside[$i]);
          }
        }
      }

      // If we split up the message before we have to concatenate it together again (dice tags)
      if (isset($inside))
      {
        $text = '';
        $num_tokens = count($outside);

        for ($j = 0; $j < $num_tokens; $j++)
        {
          $text .= $outside[$j];
          if (isset($inside[$j]))
            $text .= '[dice]'. $inside[$j] . '[/dice]';
        }
      }
      $outside_code[$key] = $text;
    }

    // If we split up the message before we have to concatenate it together again (code tags)
    if (isset($inside_code))
    {
      $outside_code = explode("\0", $input_text);
      $input_text = '';	

      $num_tokens = count($outside_code);

      for ($i = 0; $i < $num_tokens; ++$i)
      {
        $input_text .= $outside_code[$i];
        if (isset($inside_code[$i]))
          $input_text .= '[code]'. $inside_code[$i] . '[/code]';
      }
    }
    else
    {
     $input_text = $outside_code[0];
    }
    return $input_text;

  }
}

if (!function_exists('dr_split_expression'))
{
  /**
   * Helper function, search $tag in $string, set $tag_position and $founded only if matching new $tag 
   * position is lesser than existing $tag_position
   */
  function dr_search_tag($string, $tag, &$tag_position, &$founded)
  {
    if ((strpos($string, $tag) !== false) and (($tag_position === false) or ($tag_position > strpos($string, $tag))))
    {
      $tag_position = strpos($string, $tag);
      $founded = $tag;
    }
  }

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
      $op_position = false;
      $operator = '';

      dr_search_tag($string, '&gt;=', $op_position, $operator);
      dr_search_tag($string, '&lt;=', $op_position, $operator);
      dr_search_tag($string, '=',     $op_position, $operator);
      dr_search_tag($string, '&gt;', $op_position, $operator);
      dr_search_tag($string, '&lt;', $op_position, $operator);
      dr_search_tag($string, '*', $op_position, $operator);
      dr_search_tag($string, '/', $op_position, $operator);
      dr_search_tag($string, '+', $op_position, $operator);
      dr_search_tag($string, '-', $op_position, $operator);
      dr_search_tag($string, '(', $op_position, $operator);
      dr_search_tag($string, ')', $op_position, $operator);

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
    $result_expression = $expression;
    $result = $result_expression;
    $result_expression = str_replace('do', 'o', $result_expression);

    if (strpos ($result_expression, 'o') !== false)
    {
      $result = '';
      $throw = explode('o', $result_expression);
      if (isset($throw))
      {
        $results = array ();
        $num_dices = $throw[0];
        $j = 0;
        $result .= '  /*'; 
        for (; $j < $num_dices; $j++)
        {
          $results[] = dr_roll_dice((int)$throw[1]);
          if ($j > 0)
          	$result .= ', ';
          $result .= $results[$j];
        }
        $result .= '*/  '; 
        sort($results);
        $result .= $results[(($j-1)/2)];
      }
      unset($throw);
    }
    else
    if (strpos ($result_expression, 'd') !== false)
    {
      $result = '';
      $throw = explode('d', $result_expression);
      if (isset($throw))
      {
        $sum_result = 0;
        $num_dices = $throw[0];
        for ($j = 0; $j < $num_dices; $j++)
        {
          $dice_result = dr_roll_dice((int)$throw[1]);
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


if (!function_exists('dr_roll_dice'))
{
  function dr_roll_dice($faces) {
    return rand(1, $faces);
  }
}
?>
