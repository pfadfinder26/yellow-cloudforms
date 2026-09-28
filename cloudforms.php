<?php
// Cloudforms extension, https://github.com/pfadfinder26/yellow-cloudforms
// Based on Datenstrom Yellow, https://datenstrom.se/yellow/

class YellowCloudforms {
    const VERSION = "0.1.2";
    public $yellow;         // access to API

    // Handle initialisation
    public function onLoad($yellow) {
        $this->yellow = $yellow;
        $this->yellow->system->setDefault("cloudformsUrl", "");
        $this->yellow->system->setDefault("cloudformsCacheTime", "3600");
        $this->yellow->system->setDefault("cloudformsLabelSubmit", "Send");
        $this->yellow->system->setDefault("cloudformsLabelDone", "Thank you, your answer is in.");
        $this->yellow->system->setDefault("cloudformsLabelFailed", "Sorry, your answer could not be sent.");
        $this->yellow->system->setDefault("cloudformsLabelRequired", "required");
        $this->yellow->system->setDefault("cloudformsLabelOpen", "Open form in the cloud");
    }

    // Handle request, an answer is sent to the cloud and the page is shown again
    public function onRequest($scheme, $address, $base, $location, $fileName) {
        if ($this->yellow->toolbox->getServer("REQUEST_METHOD")!="POST") return 0;
        $hash = trim($this->yellow->page->getRequest("cloudform"));
        if (is_string_empty($hash) || !preg_match("/^[A-Za-z0-9]+$/", $hash)) return 0;
        if (!$this->isRequestSameSite($scheme, $address)) return $this->yellow->sendStatus(400);
        if (!is_string_empty(trim($this->yellow->page->getRequest("cloudform-website")))) {
            return $this->yellow->sendStatus(400);
        }
        $form = $this->getForm($this->getFormUrl($hash));
        $status = is_null($form) ? "failed" : $this->sendAnswers($form, $hash);
        // the answer comes back on the page it was sent from, the result in a short lived cookie
        setcookie("cloudform", $status, time()+60, "$base/", "", $scheme=="https", true);
        $location = $this->yellow->lookup->normaliseUrl($scheme, $address, $base, $location);
        return $this->yellow->sendStatus(303, $location);
    }

    // Handle page content element
    public function onParseContentElement($page, $name, $text, $attributes, $type) {
        $output = null;
        if ($name=="form" && ($type=="block" || $type=="inline")) {
            list($url) = $this->yellow->toolbox->getTextArguments($text);
            if (is_string_empty($url)) $url = $this->yellow->system->get("cloudformsUrl");
            if (is_string_empty($url)) return $this->getErrorHtml("Please add a form link!");
            $hash = $this->getShareHash($url);
            if (is_string_empty($hash)) return $this->getErrorHtml("Can't understand form link '$url'!");
            $form = $this->getForm($this->getFormUrl($hash));
            if (is_null($form)) return $this->getErrorHtml("Can't read form '$url'!");
            $page->setLastModified(time());
            $output = $this->getFormHtml($page, $form, $hash);
        }
        return $output;
    }

    // Return the form of a share, from the cache of this server if it is fresh enough
    public function getForm($url) {
        $fileName = $this->yellow->system->get("coreExtensionDirectory")."cloudforms-".
            substru(md5($url), 0, 8).".cache";
        $cacheTime = intval($this->yellow->system->get("cloudformsCacheTime"));
        if (is_file($fileName) && filemtime($fileName)+$cacheTime>time()) {
            return $this->getFormData($this->yellow->toolbox->readFile($fileName));
        }
        $context = stream_context_create(array("http" => array("timeout" => 5,
            "header" => "User-Agent: Datenstrom Yellow Cloudforms\r\n")));
        $fileData = @file_get_contents($url, false, $context, 0, 2097152);
        if ($fileData===false || strposu($fileData, "initial-state-forms-form")===false) {
            return is_file($fileName) ? $this->getFormData($this->yellow->toolbox->readFile($fileName)) : null;
        }
        $this->yellow->toolbox->writeFile($fileName, $fileData);
        return $this->getFormData($fileData);
    }

    // Return the questions of a form, the page of a share carries them as its initial state
    public function getFormData($fileData) {
        if (!preg_match("/initial-state-forms-form\"\s+value=\"([A-Za-z0-9+\/=]+)\"/", $fileData, $matches)) return null;
        $form = @json_decode(base64_decode($matches[1]), true);
        if (!is_array($form) || !isset($form["id"]) || !isset($form["questions"])) return null;
        return $form;
    }

    // Return the address of a shared form
    public function getFormUrl($hash) {
        list($server) = $this->getShare($this->yellow->system->get("cloudformsUrl"));
        return "$server/apps/forms/s/$hash";
    }

    // Return the hash of a form share, as a link from the app or as a hash
    public function getShareHash($url) {
        if (preg_match("#^(?:https?://[^/]+)?/?(?:index\.php/)?apps/forms/(?:s|embed)/([A-Za-z0-9]+)#", $url, $matches)) {
            return $matches[1];
        }
        return preg_match("/^[A-Za-z0-9]{8,}$/", $url) ? $url : "";
    }

    // Return server and hash of a form link
    public function getShare($url) {
        if (preg_match("#^(https?://[^/]+)/(?:index\.php/)?apps/forms/(?:s|embed)/([A-Za-z0-9]+)#", $url, $matches)) {
            return array($matches[1], $matches[2]);
        }
        if (preg_match("#^(https?://[^/]+)#", $url, $matches)) return array($matches[1], "");
        return array("", "");
    }

    // Send the answers of this request to the cloud
    public function sendAnswers($form, $hash) {
        $answers = array();
        foreach ($form["questions"] as $question) {
            $value = $this->yellow->page->getRequest("cloudform-".$question["id"]);
            if (is_array($value)) {
                $value = array_values(array_filter(array_map("strval", $value), function ($text) {
                    return !is_string_empty($text);
                }));
            } else {
                $value = is_string_empty(trim(strval($value))) ? array() : array(trim(strval($value)));
            }
            if (!is_array_empty($value)) $answers[strval($question["id"])] = $value;
        }
        if (is_array_empty($answers)) return "failed";
        list($server) = $this->getShare($this->yellow->system->get("cloudformsUrl"));
        $url = $server."/ocs/v2.php/apps/forms/api/v3/forms/".intval($form["id"])."/submissions";
        $content = json_encode(array("answers" => $answers, "shareHash" => $hash));
        $context = stream_context_create(array("http" => array(
            "method" => "POST",
            "timeout" => 15,
            "ignore_errors" => true,
            "header" => "OCS-APIRequest: true\r\nContent-Type: application/json\r\n".
                "Accept: application/json\r\nContent-Length: ".strlenb($content)."\r\n",
            "content" => $content)));
        $fileData = @file_get_contents($url, false, $context);
        if ($fileData===false) return "failed";
        $data = @json_decode($fileData, true);
        $statusCode = isset($data["ocs"]["meta"]["statuscode"]) ? intval($data["ocs"]["meta"]["statuscode"]) : 0;
        return ($statusCode>=100 && $statusCode<300) ? "done" : "failed";
    }

    // Check if a request comes from this website
    public function isRequestSameSite($scheme, $address) {
        $origin = "";
        if (preg_match("#^(\w+)://([^/]+)#", $this->yellow->toolbox->getServer("HTTP_REFERER"), $matches)) {
            $origin = "$matches[1]://$matches[2]";
        }
        if ($this->yellow->toolbox->getServer("HTTP_ORIGIN")) $origin = $this->yellow->toolbox->getServer("HTTP_ORIGIN");
        return $origin=="$scheme://$address";
    }

    // Return form HTML, the questions of the cloud as fields of this website
    public function getFormHtml($page, $form, $hash) {
        $status = $this->yellow->toolbox->getCookie("cloudform");
        if ($status=="done" || $status=="failed") {
            $page->setHeader("Set-Cookie", "cloudform=; Max-Age=0; Path=".
                $this->yellow->system->get("coreServerBase")."/");
            $page->setHeader("Cache-Control", "no-store");
        }
        $output = "<div class=\"cloudform\">\n";
        if ($status=="done" || $status=="failed") {
            $output .= "<p class=\"cloudform-status cloudform-".htmlspecialchars($status)."\">".
                htmlspecialchars($this->yellow->system->get($status=="done" ?
                    "cloudformsLabelDone" : "cloudformsLabelFailed"))."</p>\n";
        }
        if ($status!="done") {
            $output .= "<form class=\"cloudform-fields\" method=\"post\" action=\"".
                htmlspecialchars($page->getLocation(true))."\">\n";
            $output .= "<input type=\"hidden\" name=\"cloudform\" value=\"".htmlspecialchars($hash)."\" />\n";
            $output .= "<p class=\"cloudform-website\"><label>Website<input type=\"text\"".
                " name=\"cloudform-website\" tabindex=\"-1\" autocomplete=\"off\" /></label></p>\n";
            if (!is_string_empty($form["title"])) {
                $output .= "<h3>".htmlspecialchars($form["title"])."</h3>\n";
            }
            foreach ($form["questions"] as $question) $output .= $this->getQuestionHtml($question);
            $output .= "<p class=\"cloudform-submit\"><button type=\"submit\">".
                htmlspecialchars($this->yellow->system->get("cloudformsLabelSubmit"))."</button></p>\n";
            $output .= "</form>\n";
        }
        $output .= "<p class=\"cloudform-link\"><a href=\"".htmlspecialchars($this->getFormUrl($hash))."\">".
            htmlspecialchars($this->yellow->system->get("cloudformsLabelOpen"))."</a></p>\n";
        $output .= "</div>\n";
        return $output;
    }

    // Return one question as its fields
    public function getQuestionHtml($question) {
        $id = "cloudform-".intval($question["id"]);
        $name = "cloudform-".intval($question["id"]);
        $required = !is_array_empty($question["options"]) || $question["type"]!="multiple" ?
            !empty($question["isRequired"]) : false;
        $output = "<div class=\"cloudform-question\">\n";
        $output .= "<span class=\"cloudform-label\" id=\"$id-label\">".htmlspecialchars($question["text"]);
        if ($required) {
            $output .= " <span class=\"cloudform-required\">".
                htmlspecialchars($this->yellow->system->get("cloudformsLabelRequired"))."</span>";
        }
        $output .= "</span>\n";
        if (!is_string_empty($question["description"])) {
            $output .= "<span class=\"cloudform-description\">".htmlspecialchars($question["description"])."</span>\n";
        }
        $attributes = $required ? " required=\"required\"" : "";
        switch ($question["type"]) {
            case "long":
                $output .= "<textarea id=\"$id\" name=\"$name\" rows=\"4\"$attributes></textarea>\n";
                break;
            case "date":
                $output .= "<input type=\"date\" id=\"$id\" name=\"$name\"$attributes />\n";
                break;
            case "time":
                $output .= "<input type=\"time\" id=\"$id\" name=\"$name\"$attributes />\n";
                break;
            case "datetime":
                $output .= "<input type=\"datetime-local\" id=\"$id\" name=\"$name\"$attributes />\n";
                break;
            case "dropdown":
                $output .= "<select id=\"$id\" name=\"$name\"$attributes>\n<option value=\"\"></option>\n";
                foreach ($question["options"] as $option) {
                    $output .= "<option value=\"".htmlspecialchars($option["id"])."\">".
                        htmlspecialchars($option["text"])."</option>\n";
                }
                $output .= "</select>\n";
                break;
            case "multiple":
            case "multiple_unique":
                $unique = $question["type"]=="multiple_unique";
                $output .= "<span class=\"cloudform-options\" role=\"group\" aria-labelledby=\"$id-label\">\n";
                foreach ($question["options"] as $number=>$option) {
                    $output .= "<label><input type=\"".($unique ? "radio" : "checkbox")."\"".
                        " name=\"$name".($unique ? "" : "[]")."\" value=\"".htmlspecialchars($option["id"])."\"".
                        ($unique && $required ? $attributes : "")." /> ".
                        htmlspecialchars($option["text"])."</label>\n";
                }
                $output .= "</span>\n";
                break;
            default:
                $output .= "<input type=\"".$this->getInputType($question)."\" id=\"$id\" name=\"$name\"".
                    $this->getInputAttributes($question).$attributes." />\n";
        }
        return $output."</div>\n";
    }

    // Return the type of a field, what the question is checked for in the cloud
    public function getInputType($question) {
        switch ($this->getValidation($question)) {
            case "email":   return "email";
            case "phone":   return "tel";
            case "number":  return "number";
            default:        return "text";
        }
    }

    // Return what the browser can check and fill in by itself
    public function getInputAttributes($question) {
        $output = "";
        // "inputmode" does not survive the filter of Yellow, the type of the field says enough
        $validation = $this->getValidation($question);
        if ($validation=="regex") {
            $expression = $this->getExtraSetting($question, "validationRegex");
            if (preg_match("#^/(.*)/[a-z]*$#s", $expression, $matches)) $expression = $matches[1];
            if (!is_string_empty($expression)) {
                $output .= " pattern=\"".htmlspecialchars($expression)."\"";
            }
        }
        $autocomplete = trim(strval($question["name"]));
        if (!is_string_empty($autocomplete) && preg_match("/^[\w\- ]+$/", $autocomplete)) {
            $output .= " autocomplete=\"".htmlspecialchars($autocomplete)."\"";
        }
        return $output;
    }

    // Return how a question is checked, empty when it is not
    public function getValidation($question) {
        return strtoloweru(strval($this->getExtraSetting($question, "validationType")));
    }

    // Return a setting a question carries beside its type
    public function getExtraSetting($question, $key) {
        $extra = isset($question["extraSettings"]) ? $question["extraSettings"] : array();
        return is_array($extra) && isset($extra[$key]) ? $extra[$key] : "";
    }

    // Return error message for authors
    public function getErrorHtml($text) {
        return "<p class=\"error\">Cloudforms: ".htmlspecialchars($text)."</p>\n";
    }
}
