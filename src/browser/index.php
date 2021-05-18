<?php
$app_root = "/browser";
$content_root = "/opt/browser/content";
$session_file = "/opt/browser/sessions";
$useragent_file = "/opt/browser/useragent";
$useragent_list = "/opt/browser/useragents";
$search_url = "https://google.com/search?q=";
$log = "/dev/null";
$sessions = array();
$session = array();
$session_id = "";
include("functions.php");

header("Content-Security-Policy: " .
    "default-src 'self';" .
    "object-src 'none';" .
    "script-src 'none';" .
    "base-uri 'none';" .
    "font-src 'none';"
);

/* Handle sessions
 *
 * Handling sessions first is part of our CSRF defense, as all interactions with
 * the app require a valid session_id (SID). Any request sent to the app with
 * an invalid or missing SID will be discarded and the requester will be
 * redirected to the app root with a new, valid SID. The Same-Origin Policy
 * should prevent attackers on another site from reading this new SID.
 */

get_sessions();
if (isset($_GET["sid"])) {
    $claimed_session_id = $_GET["sid"];
    if (count($sessions) > 0) {
        foreach ($sessions as $saved_session) {
            if ($saved_session["session_id"] === $claimed_session_id) {
                $session = $saved_session;
                $session_id = $claimed_session_id;
                break;
            }
        }
    }
}
if ($session_id === "") {
    $session = new_session();
    $session_id = $session["session_id"];
    $sessions[$session_id] = $session;
    save_sessions();
    header("Location: $app_root?sid=$session_id");
    exit();
}

/* Handle actions */

// Close a tab or all tabs
if (isset($_GET["close"])) {
    $close_target = $_GET["close"];
    $prev_session_id = get_session_prev_sibling($close_target);
    $next_session_id = get_session_next_sibling($close_target);
    if ($close_target === "all") {
        system("rm -fr $content_root/* >> $log 2>&1");
        file_put_contents($session_file, "");
        header("Location: $app_root");
        exit();
    } else {
        foreach ($sessions as $saved_session_id => $saved_session) {
            if ($saved_session_id === $close_target) {
                $screenshot_path = $content_root . "/" . $saved_session["screenshot_name"];
                if (file_exists($screenshot_path)) {
                    unlink($screenshot_path);
                }
                $html_path = $content_root . "/" . $saved_session["html_name"];
                if (file_exists($html_path)) {
                    unlink($html_path);
                }
                unset($sessions[$close_target]);
                save_sessions();
                break;
            }
        }
    }
    $redirected_target_id = $session_id;
    if ($session_id === $close_target) {
        if ($next_session_id !== "") {
            $redirected_target_id = $next_session_id;
        } elseif ($prev_session_id !== "") {
            $redirected_target_id = $prev_session_id;
        }
    }
    header("Location: $app_root/?sid=$redirected_target_id&method=tabs");
    exit();
}

// Kill Firefox and abort
if (isset($_GET["abort"])) {
    system("sudo -u browser /opt/browser/abort >> $log 2>&1");
    header("Location: $app_root/");
    exit();
}

// Reset the Remote Browser
if (isset($_GET["reset"])) {
    system("sudo -u browser /opt/browser/reset >> $log 2>&1");
    header("Location: $app_root/");
    exit();
}

// Set the useragent
if (isset($_GET["set_useragent"]) && isset($_GET["useragent_id"])) {
    $useragent_id = intval($_GET["useragent_id"]);
    $useragents = get_useragents();
    if (isset($useragents[$useragent_id])) {
        file_put_contents($useragent_file, $useragents[$useragent_id]["useragent"]);
    }
    header("Location: $app_root/?sid=$session_id&method=tabs");
    exit();
}

/* Handle content */

// Parse the requested URL
if (!isset($_GET["loading"])) {
    if (isset($_GET["url"]) || isset($_POST["manual_url"])) {
        $base64_url = isset($_POST["manual_url"]) ? base64_encode($_POST["manual_url"]) : $_GET["url"];
        $url = isset($_POST["manual_url"]) ? $_POST["manual_url"] : base64_decode($base64_url);
        $url = filter_url($url);
        if ($url === false || is_valid_url($url) === false) {
            $url = isset($_GET["url"]) ? $_GET["url"] : $_POST["manual_url"];
            $url = $search_url . urlencode($url);
            $base64_url = base64_encode($url);
        }
        set_session_variable($session_id, "base64_url", $base64_url);
        set_session_variable($session_id, "url", $url);
    } else if ($session["base64_url"] && $session["url"]) {
        $base64_url = $session["base64_url"];
        $url = $session["url"];
    }
}
save_sessions();

// Parse the requested method
$methods = array("screenshot", "html", "text", "tabs", "useragent");
if (isset($_GET["method"]) && in_array($_GET["method"], $methods)) {
    $method = $_GET["method"];
} else {
    $method = "screenshot";
}
if ($method === "screenshot" || $method === "html" || $method === "text") {
    set_session_variable($session_id, "method", $method);
    save_sessions();
}

// Parse the requested screenshot size
$screenshot_size = 390;
if (isset($_POST["screenshot_size"]) || isset($_GET["screenshot_size"])) {
    $screenshot_size = isset($_POST["screenshot_size"]) ? $_POST["screenshot_size"] : $_GET["screenshot_size"];
    $screenshot_size = intval($screenshot_size);
    if (!$screenshot_size || $screenshot_size < 200 || $screenshot_size > 3000) {
        $screenshot_size = 390;
    }
}

// Show the loading screen
if (isset($_GET["loading"]) && isset($_GET["url"])) {
    $redirect_url = base64_decode($_GET["url"]);
    $url_match = "|^$app_root/\?get_screenshot&sid=[A-z0-9\+/=]{32}&url=[A-z0-9\+/=]*(?:&retake)?(?:&screenshot_size=\d{3,4})?$|";
    if (preg_match($url_match, $redirect_url) === 1) {
        $target_url = preg_split("/&/", preg_split("/url=/", $redirect_url)[1])[0];
        if (is_valid_url(base64_decode($target_url))) {
            html_head("<meta http-equiv='refresh' content='0; URL=$redirect_url'>");
            ?>
            <div class="loading-wrap">
                <h2 class="loading-text">Loading</h2><img class="loading-gif" src="art/loading.gif" width="30px">
            </div>
            <?php
            exit();
        }
    }
    show_error("Error", "The requested URL was invalid.");
    exit();
}

// Remove old content if we've been asked to retake the screenshot or text
if (isset($_POST["retake"]) || isset($_GET["retake"])) {
    if (file_exists($content_root . "/" . $session["screenshot_name"])) {
        unlink($content_root . "/" . $session["screenshot_name"]);
    }
    if (file_exists($content_root . "/" . $session["html_name"])) {
        unlink($content_root . "/" . $session["html_name"]);
    }
    if (file_exists($content_root . "/" . $session["text_name"])) {
        unlink($content_root . "/" . $session["text_name"]);
    }
}

// Take the screenshot, or return the existing screenshot if is present
if (isset($url) && isset($_GET["get_screenshot"])) {
    html_head();
    $screenshot_path = $content_root . "/" . $session["screenshot_name"];
    if (!file_exists($screenshot_path)) {
        $command = "sudo -u browser /opt/browser/screenshot '$url' $screenshot_size '$screenshot_path' >> $log 2>&1";
        file_put_contents('/tmp/command', $_GET["url"]."\n".$command);
        system($command);
    }
    ?>
    <body class="iframe-body">
        <img class="screenshot-img" src="index.php?get_image&sid=<?php echo $session_id; ?>">
    </body>
    </html>
    <?php
    exit();
}

// Return the screenshot for the specified session id
if (isset($_GET["get_image"])) {
    $screenshot_path = $content_root . "/" . $session["screenshot_name"];
    if (file_exists($screenshot_path)) {
        $size = filesize($screenshot_path);
        header("Content-Type: image/png");
        header("Content-Length: $size");
        echo file_get_contents($screenshot_path);
    }
    exit();
}

// Capture the page's HTML
if (isset($url) && $method === "html") {
    $html_path = $content_root . "/" . $session["html_name"];
    if (!file_exists($html_path)) {
        system("sudo -u browser /opt/browser/lynx-links '$url' '" . $session["html_name"] . "' >> $log 2>&1");
    }
    $html_content = file_get_contents($html_path);
    $in_app_links = get_in_app_links($html_content);
    $html_content = htmlspecialchars($html_content);
    $html_content = preg_replace("/(&\w*;)/", '<span class="entity">$1</span>', $html_content);
    $html_content = insert_in_app_links($html_content, $in_app_links);
}

// Capture the page's text
if (isset($url) && $method === "text") {
    $text_path = $content_root . "/" . $session["text_name"];
    if (!file_exists($text_path)) {
        system("sudo -u browser /opt/browser/lynx '$url' '$text_path' >> $log 2>&1");
    }
    $text_content = htmlspecialchars(file_get_contents($text_path), ENT_QUOTES);
    $text_content = preg_replace("/(&\w*;)/", '<span class="entity">$1</span>', $text_content);
}

html_head();
?>
<body>
    <?php
    if ($method === "tabs") {
        tabs_page();
    } if ($method === "useragent") {
        useragent_page();
    }  else if ($method === "screenshot") {
        browser_bar();
        if ($url) {
            $iframe_final_url = base64_encode(
                "$app_root/?get_screenshot&sid=$session_id&url=$base64_url" .
                (isset($_POST["retake"]) ? "&retake" : "") .
                "&screenshot_size=" . $screenshot_size);
            $iframe_url = "$app_root/?loading&url=$iframe_final_url&sid=$session_id";
            echo "<iframe id='screenshot-iframe' src='$iframe_url'></iframe>";
        }
    } else if ($method === "html") {
        browser_bar();
        if (isset($html_content)) {
            echo "<pre class='html-content'>$html_content</pre>";
        }
    } else if ($method === "text") {
        browser_bar();
        if (isset($text_content)) {
            echo "<pre class='text-content'>$text_content</pre>";
        }
    }
    ?>
    <div id="bottom-bar">
        <div class="bottom-bar-button screenshot<?php echo ($method === "screenshot") ? " selected" : ""; ?>">
            <a href="<?php echo add_or_update_url_param("method", "screenshot"); ?>">S</a>
        </div>
        <div class="bottom-bar-button html<?php echo ($method === "html") ? " selected" : ""; ?>">
            <a href="<?php echo add_or_update_url_param("method", "html"); ?>">H</a>
        </div>
        <div class="bottom-bar-button text<?php echo ($method === "text") ? " selected" : ""; ?>">
            <a href="<?php echo add_or_update_url_param("method", "text"); ?>">T</a>
        </div>
        <div class="bottom-bar-button tabs<?php echo ($method === "tabs") ? " selected" : ""; ?>">
            <a href="<?php echo add_or_update_url_param("method", "tabs"); ?>">=</a>
        </div>
    </div>
</body>
</html>

<?php

?>
