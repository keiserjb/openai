<?php

/**
 * A utility class for preparing strings when using OpenAI endpoints.
 */
class StringHelper {

  /**
   * Prepares text for prompt inputs by cleaning unwanted HTML, whitespace, and truncating.
   *
   * @param string $text The text to prepare.
   * @param array $removeHtmlElements HTML elements to remove.
   * @param int $max_length Maximum length of the text.
   * @return string Prepared text.
   */
  public static function prepareText($text, array $removeHtmlElements = [], $max_length = 10000) {
    $removeHtmlElements = array_merge(['pre', 'code', 'script', 'iframe'], $removeHtmlElements);
    $text = '<div>' . $text . '</div>';

    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    foreach ($removeHtmlElements as $element) {
      $nodes = $dom->getElementsByTagName($element);
      foreach ($nodes as $node) {
        $node->parentNode->removeChild($node);
      }
    }
    $text = $dom->saveHTML($dom->getElementsByTagName('div')->item(0));
    $text = html_entity_decode(strip_tags($text));
    $text = preg_replace('/\s+/', ' ', $text);
    $text = preg_replace('/[^\w.?!,\x27\x22\x20]/u', '', $text); // Allow letters, numbers, basic punctuation, and space
    if (strlen($text) > $max_length) {
      $text = substr($text, 0, $max_length);
    }
    return trim($text);
  }
}
