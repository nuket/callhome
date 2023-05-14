<?php

/*
 * A simple script to record the public IP address of my home internet connection
 * and print this in a simple table for me.
 *
 * Copy this file to a folder of your choice on a public webhost.
 *
 * Used in conjunction with a timer-run systemd service and your own public
 * webhost (which provides PHP), you can always find your home network,
 * without needing a subscription to a dynamic DNS service.
 *
 * Helps to get access to the home network if you have port forwarding enabled
 * for certain internal hosts.
 */

/*
 * Callhome
 * Copyright (c) 2023 Max Vilimpoc
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/**
 * Load the records file if it exists.
 * Increment the caller address if it is already present.
 * Prepend the new caller address if it is different.
 * Save the records file.
 *
 * @return the list of records
 */
function update_records($records_file, $caller_address) {
    $json = [];

    if (file_exists($records_file)) {
        $json = json_decode(file_get_contents($records_file), true);
    }

    if (count($json) > 0 && $json[0]['caller'] == $caller_address) {
        $json[0]['pings'] += 1;
        $json[0]['lastseen'] = gmdate('Y-m-d H:i:s e');
    }
    else {
        array_unshift($json, ['caller' => $caller_address, 'pings' => 1, 'lastseen' => gmdate('Y-m-d H:i:s e')]);
    }

    file_put_contents($records_file, json_encode($json, JSON_PRETTY_PRINT));

    return $json;
}

/**
 * Generate the webpage which shows all of the public IP addresses that
 * were used by my home internet connection.
 *
 * @return the raw HTML webpage content
 */
function print_caller_address_table($json) {
    $address_table = function ($json) {
        $output = "";

        // Add header labels
        array_unshift($json, ['caller' => "Address", 'pings' => "Pings", 'lastseen' => "Last Seen"]);

        foreach ($json as $v) {
$row = <<<END
    <div class="address">
        <div class="caller">{$v['caller']}</div>
        <div class="pings">{$v['pings']}</div>
        <div class="lastseen">{$v['lastseen']}</div>
    </div>

END;
        $output .= $row;
        }

        return $output;
    };

$page = <<<END
<!DOCTYPE html>
<html lang="en">

<head>
<title>Call Home Addresses ☎️</title>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="author" content="Max Vilimpoc" />
<style>
    body     { width: 800px; margin: auto; background-color: #d8e2dc; font-size: large; font-family: sans-serif; }

    .title   { margin: 1em 0; font-size: 3em; }

    div.address { display: grid; grid-template-columns: 1fr 1fr 1fr; padding-top: 0.25em; }
    div.address:first-of-type { font-weight: bold; }

    @media only screen
    and (min-device-width : 320px)
    and (max-device-width : 480px) {
        body { width: 95%; }
        .title { font-size: 4em; text-align: center; }

        div.address { display: grid; grid-template-columns: 1fr 1fr; padding-top: 1em; font-size: 10pt; }
        div.pings { display: none; }
    }
</style>
</head>

<body>
<div class="title">Call Home Addresses ☎️</div>
<div class="addresses">
{$address_table($json)}
</div>
</body>

</html>
\n
END;

    return $page;
}

/**
 * Entry point
 *
 * If you pass ?caller to the URL string, the script will pretend that the
 * request came from that address, otherwise it will use the real public address
 * of your current internet connection. This is used for testing.
 *
 * @param  caller  Pass query string parameter to URL: ?caller=172.16.20.10 to test various addresses
 */
function main($caller) {
    if (filter_var($caller, FILTER_VALIDATE_IP)) {
        $json = update_records('records.json', $caller);
        $page = print_caller_address_table($json);
        echo $page;
    }
    else {
        echo $caller;
    }
}

if (array_key_exists('caller', $_REQUEST)) {
    main($_REQUEST['caller']);
}
else {
    main($_SERVER['REMOTE_ADDR']);
}

?>
