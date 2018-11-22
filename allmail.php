<?php

  $USER = "YOUR MAIL LOGIN";
  $PASSWORD = "YOUR MAIL PASSWORD";
  $API_KEY_2CAPTCHA = "YOUR 2CAPTCHA API KEY";

  $DEBUG = FALSE;

  // skip dom warnings
  error_reporting(E_ALL & ~E_WARNING);

  include "2captcha.php";

  $cookies = "cookies.txt";

  $ch = curl_init();

  curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
  curl_setopt($ch, CURLOPT_ENCODING, "gzip");
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

  if ($DEBUG) {
    $stderr = "stderr.txt";
    if (file_exists($stderr)) {
      unlink($stderr);
    }

    $stderr_file = fopen($stderr, 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $stderr_file);
    curl_setopt($ch, CURLOPT_VERBOSE, TRUE);

    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
  }

  $url = "https://www.amazon.com/gp/aws/cart/add.html?ASIN.1=B07D3H3NKD&Quantity.1=999";

  $file = "url.html";
  $html = getHtml($url, $ch, $file);
  $form = getElement($html, "form", array("method" => "GET"), array("role" => "search"));
  $html = submitTheForm($url, $form, $ch, "continueoN.html");
  $form = getElement($html, "form", array("id" => "activeCartViewForm"), array());
  submitTheForm($url, $form, $ch, "update.html");
  return;
  if ($html) {
    $html = submitForm($html, $ch, "");
    $html = submitForm($html, $ch, "");
 
    while (is_null($gmail = getLink("Gmail", $html))) {
      $html = submitForm($html, $ch, "");
    }

    $html = getHtml($gmail, $ch, "");
    $html = submitFormWithText($html, "basic HTML view", $ch, "");

    $html = page("All Mail", $html, $ch, "All Mail.html");
  }

  curl_close($ch);

  function getAttribute($html, $tag, $attribName, $attribValue, $attribute) {
    $dom = new DOMDocument('1.0', 'iso-8859-1');
    $dom->loadHTML($html);

    $tagNode;

    $tags = $dom->getElementsByTagName($tag);
    foreach($tags as $tag) {
        foreach($tag->attributes as $tag_attribute) {
          if (preg_match("/" . $attribName . "/i", $tag_attribute->nodeName) == 1
            && preg_match("/" . $attribValue . "/i", $tag_attribute->nodeValue) == 1) {
            $tagNode = $tag;
            break;
          }
        }
    }
    if (isset($tagNode)) {
        foreach($tagNode->attributes as $tag_attribute) {
          if (preg_match("/" . $attribute . "/i", $tag_attribute->nodeName) == 1) {
            return $tag_attribute->nodeValue;
          }
        }
    }
  }

  function getAttributeValue($html, $tag, $attribute) {
    $dom = new DOMDocument('1.0', 'iso-8859-1');
    $dom->loadHTML($html);

    $tags = $dom->getElementsByTagName($tag);
    foreach($tags as $tag) {
      foreach($tag->attributes as $tag_attribute) {
        if (preg_match("/" . $attribute . "/i", $tag_attribute->nodeName) == 1) {
          return $tag_attribute->nodeValue;
        }
      }
    }
  }

  function getElement($html, $name, $attributesToMatch, $attributesToMismatch) {
    $dom = new DOMDocument('1.0', 'iso-8859-1');
    $dom->loadHTML($html);
    $elements = $dom->getElementsByTagName($name);
    foreach($elements as $element) {
      $elementAttributes = $element->attributes;
      $elementAttributesMatch = TRUE;
      foreach($attributesToMatch as $attributeToMatchName => $attributeToMatchValue) {
        $attributeMatch = FALSE;
        foreach($elementAttributes as $elementAttribute) {
          $elementAttributeName = $elementAttribute->nodeName;
          $elementAttributeValue = $elementAttribute->nodeValue;
          if (strtolower($attributeToMatchName) == strtolower($elementAttributeName)
            && strtolower($attributeToMatchValue) == strtolower($elementAttributeValue)) {
              $attributeMatch = TRUE;
              break;
            }
        }
        if ($attributeMatch) {
          continue;
        } else {
          $elementAttributesMatch = FALSE;
          break;
        }
      }
      if ($elementAttributesMatch) {
        $elementAttributesMismatch = TRUE;
        foreach($attributesToMismatch as $attributeToMismatchName => $attributeToMismatchValue) {
          $attributeMatch = FALSE;
          foreach($elementAttributes as $elementAttribute) {
            $elementAttributeName = $elementAttribute->nodeName;
            $elementAttributeValue = $elementAttribute->nodeValue;
            if (strtolower($attributeToMismatchName) == strtolower($elementAttributeName)
              && strtolower($attributeToMismatchValue) == strtolower($elementAttributeValue)) {
                $attributeMatch = TRUE;
                break;
              }
          }
          if ($attributeMatch) {
            $elementAttributesMismatch = FALSE;
            break;
          } else {
            continue;
          }
        }
        if ($elementAttributesMismatch) {
          return $element;
        }
      }
    }
    return NULL;
  }

  function getHtml($url, $ch, $file) {
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 0);
    $html = curl_exec($ch);
    if (trim($file)) {
      file_put_contents($file, $html);
    }
    return $html;
  }

  function getLink($linkText, $html) {
    $dom = new DOMDocument('1.0', 'iso-8859-1');
    $dom->loadHTML($html);

    $anchors = $dom->getElementsByTagName("a");
    foreach($anchors as $anchor) {
      if (preg_match("/" . $linkText . "/i", $anchor->textContent) == 1){
        foreach($anchor->attributes as $anchor_attribute) {
          if (preg_match("/href/i", $anchor_attribute->nodeName) == 1){
            return $anchor_attribute->nodeValue;
          }
        }
      }
    }
    return NULL;
  }

  function getOrPostData($url, $method, $post_data, $ch, $file) {
    if (preg_match("/post/i", $method) == 1) {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    } else {
      if (preg_match("/\?/", $url) === 0) {
        $url .= "?";
      }
      $url .= http_build_query($post_data);
    }
    return getHtml($url, $ch, $file);
  }

  function getOrPostForm($url, $method, $dom, $ch, $file) {
    $post_data = array();

    foreach($dom->getElementsByTagName("input") as $input) {
      if (preg_match("/input/i", $input->nodeName) == 1) {
        $name = "";
        $value = "";
        foreach($input->attributes as $input_attribute) {
          $input_attr_name = $input_attribute->nodeName;
          $input_attr_value = $input_attribute->nodeValue;

          if (preg_match("/name/i", $input_attr_name) == 1){
            if (preg_match("/.+/i", $input_attr_value) == 1){
              $name = $input_attr_value;
            } else {
              // ignore input with no name
              break;
            }
          } else if (preg_match("/value/i", $input_attr_name) == 1){
            $value = $input_attr_value;
          }
        }
        if (preg_match("/.+/i", $name) != 1){
          // ignore input with no name
          continue;
        }
        global $USER, $PASSWORD;
        if (preg_match("/email/i", $name) == 1) {
          $value = $USER;
        } else if (preg_match("/Passwd/i", $name) == 1) {
          $value = $PASSWORD;
        }
        $post_data[$name] = $value;
      }
    }

    if (isset($post_data["logincaptcha"])) {
      $captcha_url = $post_data["url"];
      $captcha_img = 'Captcha.jpg';
      file_put_contents($captcha_img, file_get_contents($captcha_url));
      global $API_KEY_2CAPTCHA;
      $captcha = recognize($captcha_img, $API_KEY_2CAPTCHA);
      if (!$captcha) {
        echo "Failed to solve captcha\n";
        $captcha = "tryagain";
      }
      $post_data["logincaptcha"] = $captcha;
    }

    return getOrPostData($url, $method, $post_data, $ch, $file);
  }

  function page($linkText, $html, $ch, $file) {
    $link = getLink($linkText, $html);
    if (is_null($link)) {
      return NULL;
    } else {
      if (preg_match("/^http/i", trim($link)) != 1) {
        $appUrl = getAttributeValue($html, "base", "href");
        if (!$appUrl) {
          $appUrl = getAttribute($html, "link", "rel", "canonical", "href");
        }
        $link = $appUrl . $link;
      }
      return getHtml($link, $ch, $file);
    }
  }

  function submitForm($html, $ch, $file) {
    $dom = new DOMDocument('1.0', 'iso-8859-1');
    $dom->loadHTML($html);

    $forms = $dom->getElementsByTagName("form");

    foreach($forms as $form) {
      foreach($form->attributes as $form_attribute) {
        $form_attribute_name = $form_attribute->nodeName;
        $form_attribute_value = $form_attribute->nodeValue;
        if (preg_match("/method/i", $form_attribute_name) == 1) {
          $method = $form_attribute_value;
        }
        if (preg_match("/action/i", $form_attribute_name) == 1) {
          $action = $form_attribute_value;
        }
      }
    }

    if (preg_match("/^http/i", trim($action)) != 1) {
      $appUrl = getAttribute($html, "meta", "name", "application-url", "content");
      $action = $appUrl . $action;
    }

    return getOrPostForm($action, $method, $dom, $ch, $file);
  }

  function submitFormWithText($html, $formContent, $ch, $file) {
    $dom = new DOMDocument('1.0', 'iso-8859-1');
    $dom->loadHTML($html);

    $forms = $dom->getElementsByTagName("form");

    foreach($forms as $form) {
      if (preg_match("/" . $formContent . "/i", $form->textContent) == 1) {
        foreach($form->attributes as $form_attribute) {
          $form_attribute_name = $form_attribute->nodeName;
          $form_attribute_value = $form_attribute->nodeValue;
          if (preg_match("/method/i", $form_attribute_name) == 1) {
            $method = $form_attribute_value;
          }
          if (preg_match("/action/i", $form_attribute_name) == 1) {
            $action = $form_attribute_value;
          }
        }
      }
    }

    if (preg_match("/^http/i", trim($action)) != 1) {
      $appUrl = getAttribute($html, "meta", "name", "application-url", "content");
      $action = $appUrl . $action;
    }

    return getOrPostData($action, $method, array(), $ch, $file);
  }

  function submitTheForm($url, $form, $ch, $file) {
    $formData = array();
    $action = $form->getAttribute("action");
    $method = $form->getAttribute("method");
    $inputs = $form->getElementsByTagName("input");
    foreach($inputs as $input) {
      $inputName = $input->getAttribute("name");
      $inputValue = $input->getAttribute("value");
      if (strtolower($input->getAttribute("type")) === "submit") {
        if (stripos($inputName, "update-quantity") == FALSE) {
          continue;
        }
      } else if (preg_match("/^quantity\..+/", $inputName) == 1) {
        $inputValue = 999;
      }
      $formData[$inputName] = $inputValue;
    }
    $selects = $form->getElementsByTagName("select");
    foreach($selects as $select) {
      $options = $select->getElementsByTagName("option");
      foreach($options as $option) {
        if ($option->getAttribute("selected")) {
          $selectName = $select->getAttribute("name");
          $formData[$selectName] = $option->getAttribute("value");
          break;
        }
      }
    }
    echo http_build_query($formData) . "\n";
    return getOrPostData(implode ("/", array_slice(preg_split("/\//", $url, 4), 0, 3)) . $action
    , $method, $formData, $ch, $file);
  }
?>