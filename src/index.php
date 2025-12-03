<?php

declare(strict_types=1);

// load functions
require_once __DIR__ . "/../vendor/autoload.php";
require_once "stats.php";
require_once "card.php";

// Environment variables are loaded by the Vercel platform

// The TOKEN environment variable is required.
// It is configured in the Vercel project settings.
if (!isset($_SERVER["TOKEN"])) {
    renderOutput("Missing token environment variable.", 500);
}

// set cache to refresh once per three horus
$cacheMinutes = 3 * 60 * 60;
header("Expires: " . gmdate("D, d M Y H:i:s", time() + $cacheMinutes) . " GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: public, max-age=$cacheMinutes");

// redirect to demo site if user is not given
if (!isset($_REQUEST["user"])) {
    header("Location: demo/");
    exit();
}

try {
    // get streak stats for user given in query string
    $user = preg_replace("/[^a-zA-Z0-9\-]/", "", $_REQUEST["user"]);
    $startingYear = isset($_REQUEST["starting_year"]) ? intval($_REQUEST["starting_year"]) : null;
    $contributionGraphs = getContributionGraphs($user, $startingYear);
    $contributions = getContributionDates($contributionGraphs);
    if (isset($_GET["mode"]) && $_GET["mode"] === "weekly") {
        $stats = getWeeklyContributionStats($contributions);
    } else {
        // split and normalize excluded days
        $excludeDays = normalizeDays(explode(",", $_GET["exclude_days"] ?? ""));
        $stats = getContributionStats($contributions, $excludeDays);
    }
    renderOutput($stats);
} catch (InvalidArgumentException | AssertionError $error) {
    error_log("Error {$error->getCode()}: {$error->getMessage()}");
    if ($error->getCode() >= 500) {
        error_log($error->getTraceAsString());
    }
    renderOutput($error->getMessage(), $error->getCode());
}
