<?php

/* Sessions */

function new_session() {
    return array(
        "session_id" => new_id(),
        "screenshot_name" => new_id(),
        "html_name" => new_id(),
        "text_name" => new_id(),
        "url" => "",
        "base64_url" => "",
        "method" => "screenshot"
    );
}

function get_sessions() {
    global $session_file;
    global $sessions;
    $session_content = file_get_contents($session_file);
    if ($session_content) {
        $_sessions = json_decode($session_content, true);
        if ($_sessions) {
            $sessions = $_sessions;
        }
    }
}

function save_sessions() {
    global $session_file;
    global $sessions;
    file_put_contents($session_file, json_encode($sessions));
}

function set_session_variable($session_id, $variable_name, $variable_value) {
    global $sessions;
    foreach ($sessions as $index => $session) {
        if ($sessions[$index]["session_id"] === $session_id) {
            $sessions[$index][$variable_name] = $variable_value;
        }
    }
}

function get_session_prev_sibling($session_id) {
    global $sessions;
    $prev_session_id = "";
    foreach ($sessions as $saved_session_id => $session) {
        if ($saved_session_id === $session_id) {
            return $prev_session_id;
        }
        $prev_session_id = $saved_session_id;
    }
    return "";
}

function get_session_next_sibling($session_id) {
    global $sessions;
    $found = false;
    foreach ($sessions as $saved_session_id => $session) {
        if ($found) {
            return $saved_session_id;
        }
        if ($saved_session_id === $session_id) {
            $found = true;
        }
    }
    return "";
}

/* Cryptography */

function new_id($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/* HTML */

function html_head($extra_tag = "") {
    global $app_root;
?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="application-name" content="Remote Browser">
        <meta name="apple-mobile-web-app-title" content="Remote Browser">
        <meta name="msapplication-starturl" content="index.php">
        <link rel="manifest" href="<?php echo $app_root; ?>/manifest.json">
        <link rel="apple-touch-icon" href="<?php echo $app_root; ?>/art/touch-icon-iphone-retina.png">
        <link rel="apple-touch-icon" sizes="152x152" href="<?php echo $app_root; ?>/art/touch-icon-ipad.png">
        <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $app_root; ?>/art/touch-icon-iphone-retina.png">
        <link rel="apple-touch-icon" sizes="167x167" href="<?php echo $app_root; ?>/art/touch-icon-ipad-retina.png">
        <link rel="stylesheet" href="<?php echo $app_root; ?>/browser-style.css">
        <title>Remote Browser</title>
        <?php if (isset($extra_tag) && $extra_tag) {
            echo $extra_tag;
        } ?>
    </head>
<?php
}

function browser_bar() {
    global $app_root;
    global $screenshot_size;
    global $method;
    global $session_id;
    global $url;
?>
    <div id="browser-bar">
        <form method="POST" action="<?php echo $app_root . "/?method=" . $method . "&sid=" . $session_id; ?>">
            <select name="screenshot_size">
                <option value="390" <?php if (isset($screenshot_size) && $screenshot_size === 390) {
                                        echo "selected";
                                    } ?>>390</option>
                <option value="640" <?php if (isset($screenshot_size) && $screenshot_size === 640) {
                                        echo "selected";
                                    } ?>>640</option>
                <option value="800" <?php if (isset($screenshot_size) && $screenshot_size === 800) {
                                        echo "selected";
                                    } ?>>800</option>
                <option value="1200" <?php if (isset($screenshot_size) && $screenshot_size === 1200) {
                                            echo "selected";
                                        } ?>>1200</option>
                <option value="2000" <?php if (isset($screenshot_size) && $screenshot_size === 2000) {
                                            echo "selected";
                                        } ?>>2000</option>
            </select>
            <input type="text" name="manual_url" value="<?php echo $url; ?>" autocapitalize="off" autocorrect="off">
            <input type="hidden" name="retake" value="true">
            <input type="submit" value="Go">
        </form>
    </div>
    <?php
}

function tabs_page() {
    global $app_root;
    global $sessions;
    global $session_id;
    echo "<div class='tab-list'>";
    foreach ($sessions as $tab_session_id => $session) {
        $tab_title = htmlspecialchars($session["url"]);
        if (!$tab_title) {
            $tab_title = "blank";
        }
        $tab_url = get_base_url() . "?sid=" . $tab_session_id . "&method=" . $session["method"];
    ?>
        <div class="list-entry<?php echo (isset($_GET["sid"]) && $_GET["sid"] === $tab_session_id) ? " selected" : ""; ?>">
            <a class="list-entry-link" href="<?php echo $tab_url; ?>">
                <span class="list-entry-title"><?php echo $tab_title; ?></span>
            </a>
            <a class="tab-icon-link" href="<?php echo add_or_update_url_param("close", $tab_session_id); ?>">
                <span class="list-entry-icon">❌</span>
            </a>
        </div>
    <?php
    }
    ?>
    <br>
    <div class="list-entry first">
        <a class="list-entry-link" href="<?php echo $app_root; ?>">
            <span class="list-entry-title">New Tab</span>
        </a>
        <a class="tab-icon-link" href="<?php echo $app_root; ?>">
            <span class="tab-new">➕</span>
        </a>
    </div>
    <div class="list-entry">
        <a class="list-entry-link" href="<?php echo $app_root . "?sid=" . $session_id; ?>&method=tabs&close=all">
            <span class="list-entry-title">Close All Tabs</span>
        </a>
    </div>
    <br>
    <div class="list-entry first">
        <a class="list-entry-link" href="<?php echo $app_root . "?sid=" . $session_id; ?>&method=useragent">
            <span class="list-entry-title">Useragent</span>
            <span class="settings-selected-option"><?php echo get_current_useragent_name(); ?> <span class="caret">v</span></span>
        </a>
    </div>
    <br>
    <div class="list-entry red first">
        <a class="list-entry-link" href="<?php echo $app_root . "?sid=" . $session_id; ?>&abort">
            <span class="list-entry-title">Kill Remote Browser</span>
        </a>
    </div>
    <div class="list-entry red">
        <a class="list-entry-link" href="<?php echo $app_root . "?sid=" . $session_id; ?>&reset">
            <span class="list-entry-title">Reset Remote Browser</span>
        </a>
    </div>
<?php
    echo "</div>";
}

function useragent_page() {
    global $app_root;
    global $session_id;
    $current_useragent_id = get_current_useragent_id();
    $useragents = get_useragents();
    echo "<div class='tab-list'>";
    foreach ($useragents as $useragent_id => $useragent_option) {
        ?>
        <div class="list-entry">
            <a class="list-entry-link" href="<?php echo $app_root . "?sid=" . $session_id; ?>&set_useragent&useragent_id=<?php echo $useragent_id; ?>">
                <span class="list-entry-title"><?php echo $useragent_option["name"]; ?></span>
                <?php if ($useragent_id == $current_useragent_id) {
                    ?>
                    <span class="list-entry-icon">✔️</span>
                    <?php
                } ?>
            </a>
        </div>
        <?php
    }
}

function show_error($title, $message) {
    html_head();
    ?>
    <body>
        <br>
        <div class="error">
            <h1>&#9888;&nbsp;<?php echo $title; ?></h1>
            <span><?php echo $message; ?></span>
        </div>
    </body>
    </html>
    <?php
}

/* URLs */

function get_base_url() {
    return basename($_SERVER["PHP_SELF"]);
}

function get_url() {
    $params = $_GET;
    return basename($_SERVER["PHP_SELF"]) . "?" . http_build_query($params);
}

function add_or_update_url_param($name, $value) {
    $params = $_GET;
    unset($params[$name]);
    if ($value) {
        $params[$name] = $value;
    }
    return basename($_SERVER["PHP_SELF"]) . "?" . http_build_query($params);
}

function filter_url($url) {
    $url = preg_replace("/\\\\/", "%5C", $url);
    $url = preg_replace("/\"/", "%22", $url);
    $url = preg_replace("/'/", "%27", $url);
    $url = preg_replace("/</", "%3C", $url);
    $url = preg_replace("/>/", "%3E", $url);
    $url = preg_replace("/`/", "%60", $url);
    $url = preg_replace("/ /", "%20", $url);
    $url = preg_replace("/\\$/", "%24", $url);
    $url = preg_replace("/\(/", "%28", $url);
    $url = preg_replace("/\)/", "%29", $url);
    $url = filter_var($url, FILTER_SANITIZE_URL);

    if (is_valid_url($url)) {
        return $url;
    }
    return false;
}

function is_valid_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL);
}

/* Useragents */

function get_useragents() {
    global $useragent_list;
    return json_decode(file_get_contents($useragent_list), true);
}

function get_current_useragent_id() {
    global $useragent_file;
    $useragents = get_useragents();
    $current_useragent = file_get_contents($useragent_file);
    foreach ($useragents as $useragent_id => $useragent_option) {
        if ($useragent_option["useragent"] === $current_useragent) {
            return $useragent_id;
        }
    }
    return false;
}

function get_current_useragent_name() {
    global $useragent_file;
    $useragents = get_useragents();
    $current_useragent = trim(file_get_contents($useragent_file));
    foreach ($useragents as $useragent_id => $useragent_option) {
        if ($useragent_option["useragent"] === $current_useragent) {
            return $useragent_option["name"];
        }
    }
    return false;
}

function set_useragent($id) {
    global $useragent_file;
    $id = intval($id);
    $useragents = get_useragents();
    if (isset($useragents[$id])) {
        file_put_contents($useragent_file, $useragents[$id]["useragent"]);
    }
}

/* Content handling */

function get_in_app_links($html_content) {
    global $app_root;
    global $session_id;
    $in_app_links = array();
    $urls = array();
    preg_match_all('/^ +\d+\. +((?:http|tel|mailto).+)$/m', $html_content, $urls);
    foreach ($urls[1] as $url) {
        $url = filter_url($url);
        if ($url !== false) {
            array_push($in_app_links, "$app_root/?url=" . base64_encode($url) . "&sid=$session_id&retake");
        }
    }
    return $in_app_links;
}

function insert_in_app_links($html_content, $in_app_links) {
    global $app_root;
    global $session_id;

    // Remove repeated characters, e.g. 300 underscores when Lynx simulates an HR tag
    $html_content = preg_replace("/(.)\\1{100,}/", "", $html_content);

    // Fix issues where references markers are on the line preceeding their link text, e.g.
    //
    //   [45]
    //   This is a link
    //
    // becomes
    //
    //   [45]This is a link
    //
    // This is primarily to fix Google saerch results using mobile user agents
    $html_content = preg_replace("/^( *\[\d*\])\n *(\n *)?/m", "$1", $html_content);

    // Replace references with a link, e.g.
    //
    //   [45]This is a link
    //
    // becomes
    //
    //   <a href="...">This</a> is a link
    $html_content = preg_replace_callback('/\[(\d*)\]([^ \n]*)/m', function ($matches) use ($in_app_links) {
        $text = $matches[2] ? $matches[2] : "[" . $matches[1] . "]";
        return "<a href='" . $in_app_links[(intval($matches[1]) - 1)] . "'>" . $text . "</a>";
    }, $html_content);
    return $html_content;
}
