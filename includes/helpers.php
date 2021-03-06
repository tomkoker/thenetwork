<?php

/**
 * Alerts to user with message.
 */
function alert($message, $type) //$type = "success", "info", "warning", or "danger"
{
  render("alert.php", ["message" => $message, "type" => $type]);
}

/**
 * Logs out current user, if any.  Based on Example #1 at
 * http://us.php.net/manual/en/function.session-destroy.php.
 */
function logout()
{
    // unset any session variables
  $_SESSION = [];

  // expire cookie
  if (!empty($_COOKIE[session_name()]))
  {
    setcookie(session_name(), "", time() - 42000);
  }

  // destroy session
  session_destroy();
}

/**
 * Redirects user to location, which can be a URL or
 * a relative path on the local host.
 *
 * http://stackoverflow.com/a/25643550/5156190
 *
 * Because this function outputs an HTTP header, it
 * must be called before caller outputs any HTML.
 */
function redirect($location)
{
  if (headers_sent($file, $line))
  {
    trigger_error("HTTP headers already sent at {$file}:{$line}", E_USER_ERROR);
  }
  header("Location: {$location}");
  exit;
}

/**
 * Renders view, passing in values.
 */
function render($view, $values = [])
{
  // if view exists, render it
  if (file_exists("../views/{$view}"))
  {
    // extract variables into local scope
    extract($values);

    // render view (between header and footer)
    require("../views/header.php");
    require("../views/{$view}");
    require("../views/footer.php");
    exit;
  }

  // else error
  else
  {
    trigger_error("Invalid view: {$view}", E_USER_ERROR);
  }
}

/**
 * Converts string into something that is
 * more url-friendly
 */
function shortname($string)
{
  $string = strtolower($string);
  $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
  $string = preg_replace("/[\s-]+/", " ", $string);
  //$string = preg_replace("/[\s_]/", "-", $string);
  // No dashes for now
  $string = preg_replace("/[\s_]/", "", $string);
  return $string;
}

/**
 * Converts timestamp into something easier to read
 */
function timeAgo($time)
{
  $elapsed = time() - $time;
  //return $elapsed;
  if ($elapsed < 60)
  {
    return "Just now";
  }

  $conversions = array(
    360 * 24 * 60 * 60 => 'year',
    30 * 24 * 60 * 60 => 'month',
    7 * 24 * 60 * 60 => 'week',
    24 * 60 * 60 => 'day',
    60 * 60 => 'hour',
    60 => 'minute'
  );

  foreach($conversions as $seconds => $string)
  {
    if($elapsed >= $seconds)
    {
      $num = round($elapsed/$seconds);
      return $num . ' ' . $string . ( $num > 1 ? 's' : '' ) . ' ago';
    }
  }
}

/**
 * Creates an array of posts in a format to print on the page given a mysql
 * reponse of posts.
 */
function formPosts($rows)
{
  $posts = [];

  foreach ($rows as $row) {
    $users = Lib::query("SELECT * FROM users WHERE id = ?", $row["user_id"]);
    $user = $users[0]; //first and only user

    $votes = Lib::query("SELECT * FROM votes WHERE post_id = ?", $row["id"]);
    $numberLikes = count($votes);

    $userVotes = Lib::query("SELECT * FROM votes WHERE post_id = ? AND user_id = ?", $row["id"], $_SESSION["id"]);
    $liked = false;
    if(count($userVotes) == 1){
      $liked = true;
    }

    $topics = Lib::query("SELECT * FROM topics WHERE id = ?", $row["topic_id"]);
    $topic = $topics[0];

    $posts[] = [
      "id" => $row["id"],
      "user" => $user,
      "date" =>  timeAgo(strtotime($row["date"])),
      "text" => $row["text"],
      "likes" => $numberLikes,
      "liked" => $liked,
      "topic" => $topic
    ];
  }
  return $posts;
}

function pageInfo($limit, $count)
{
  $page = isset($_GET["page"]) && (intval($_GET["page"]) > 1) ? $_GET["page"] : 1;
  $pages = ceil( $count / $limit);
  $start = ($page-1) * $limit;
  $last = ($page >= $pages) ? true : false;
  return [
    "page" => $page,
    "start" => $start,
    "last" => $last
  ];
}

function pageQuery($page)
{
  $params = array_merge($_GET, array("page" => $page));
  return http_build_query($params);
}

?>