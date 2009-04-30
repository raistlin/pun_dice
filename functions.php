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
 */

if (!defined('FORUM')) die();

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

    for($i = 0; $i < count($inside); $i++)
    {
      $exp = explode('d', $inside[$i]);
      if (isset($exp)) 
      {
        $inside[$i] = '(' . $inside[$i] . ') : ';
        $sum_result = 0;
        $num_dices = $exp[0];
        for ($j = 0; $j < $num_dices; $j++)
        {
          $dice_result = roll_dice($exp[1]);
          $sum_result += $dice_result;
          if ($j > 0)
            $inside[$i] .= ' + ';
          $inside[$i] .= $dice_result;

        }
        $inside[$i] .= ' = ' . $sum_result;
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
          $text .= '[dice]'.$inside[$i].'[/dice]';
      }
    }

    return $text;
    
  }
}

if (!function_exists('roll_dice'))
{
  function roll_dice($faces) {
    return rand(1, $faces);
  }
}
?>
