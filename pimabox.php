<?php
// ============================================================
// pimabox — pimabox.php
// ============================================================

require_once __DIR__ . '/config.php';
date_default_timezone_set(TIMEZONE);
session_start();

// ---- Brute-force protection ----
$maxAttempts = defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 5;
$lockoutSecs = defined('LOCKOUT_SECONDS')    ? LOCKOUT_SECONDS    : 900;
$attempts    = $_SESSION['login_attempts']   ?? 0;
$lockedUntil = $_SESSION['locked_until']     ?? 0;
$isLocked    = time() < $lockedUntil;

// ---- Auth ----
if (!$isLocked && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === STATS_PASSWORD) {
        session_regenerate_id(true);
        $_SESSION['pimabox_auth']   = true;
        $_SESSION['login_attempts'] = 0;
        $_SESSION['locked_until']   = 0;
    } else {
        $attempts++;
        $_SESSION['login_attempts'] = $attempts;
        if ($attempts >= $maxAttempts) {
            $_SESSION['locked_until'] = time() + $lockoutSecs;
            $isLocked = true;
        }
        $authError = true;
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}
$authed = !empty($_SESSION['pimabox_auth']);

// ---- Branding ----
$brandColor   = defined('BRAND_COLOR')    ? BRAND_COLOR    : '#0d9488';
$brandLogo    = defined('BRAND_LOGO')     ? BRAND_LOGO     : '';
$brandName    = defined('BRAND_NAME')     ? BRAND_NAME     : 'pimabox';
$brandFont    = 'system-ui, -apple-system, sans-serif';
$advancedMode = defined('ADVANCED_MODE')  ? ADVANCED_MODE  : false;

// ---- Pimabox logos (base64 embedded — no external file needed) ----
$pimaboxLogoDark  = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyBpZD0iRWJlbmVfMiIgZGF0YS1uYW1lPSJFYmVuZSAyIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNDMuOSA1Ny40MiI+CiAgPGRlZnM+CiAgICA8c3R5bGU+CiAgICAgIC5jbHMtMSB7CiAgICAgICAgZmlsbDogI2YzZjNmMzsKICAgICAgfQoKICAgICAgLmNscy0yIHsKICAgICAgICBmaWxsOiAjMDg5NDg4OwogICAgICB9CiAgICA8L3N0eWxlPgogIDwvZGVmcz4KICA8ZyBpZD0iRWJlbmVfMS0yIiBkYXRhLW5hbWU9IkViZW5lIDEiPgogICAgPGc+CiAgICAgIDxwYXRoIGNsYXNzPSJjbHMtMSIgZD0iTTAsMTMuMDhoNS40NnY0LjVjMS4yOC0xLjY0LDIuODktMi45Miw0LjgzLTMuODQsMS45NC0uOTIsNC4xMS0xLjM4LDYuNTEtMS4zOCwzLDAsNS43Mi43NCw4LjE2LDIuMjIsMi40NCwxLjQ4LDQuMzYsMy41MSw1Ljc2LDYuMDksMS40LDIuNTgsMi4xLDUuNDUsMi4xLDguNjFzLS43LDYuMDItMi4xLDguNThjLTEuNCwyLjU2LTMuMzIsNC41OC01Ljc2LDYuMDYtMi40NCwxLjQ4LTUuMTgsMi4yMi04LjIyLDIuMjItMi4zMiwwLTQuNDUtLjQ1LTYuMzktMS4zNS0xLjk0LS45LTMuNTMtMi4xOS00Ljc3LTMuODd2MTYuNUgwVjEzLjA4Wk02Ljk5LDM1LjEzYy45NCwxLjc0LDIuMjMsMy4xMSwzLjg3LDQuMTEsMS42NCwxLDMuNDYsMS41LDUuNDYsMS41czMuODEtLjUsNS40My0xLjVjMS42Mi0xLDIuODgtMi4zNywzLjc4LTQuMTEuOS0xLjc0LDEuMzUtMy42OSwxLjM1LTUuODVzLS40Ni00LjEyLTEuMzgtNS44OGMtLjkyLTEuNzYtMi4xOC0zLjE0LTMuNzgtNC4xNC0xLjYtMS0zLjQtMS41LTUuNC0xLjVzLTMuODIuNS01LjQ2LDEuNWMtMS42NCwxLTIuOTMsMi4zOC0zLjg3LDQuMTQtLjk0LDEuNzYtMS40MSwzLjcyLTEuNDEsNS44OHMuNDcsNC4xMSwxLjQxLDUuODVaIi8+CiAgICAgIDxwYXRoIGNsYXNzPSJjbHMtMSIgZD0iTTM5Ljg0LDIuNTljLjY0LS42NCwxLjQ4LS45NiwyLjUyLS45NnMxLjg4LjMyLDIuNTIuOTZjLjY0LjY0Ljk2LDEuNDguOTYsMi41MnMtLjMyLDEuODgtLjk2LDIuNTJjLS42NC42NC0xLjQ4Ljk2LTIuNTIuOTZzLTEuODgtLjMyLTIuNTItLjk2Yy0uNjQtLjY0LS45Ni0xLjQ4LS45Ni0yLjUycy4zMi0xLjg4Ljk2LTIuNTJaTTM5LjU0LDE4LjQyaDUuNTh2MjdoLTUuNTh2LTI3WiIvPgogICAgICA8cGF0aCBjbGFzcz0iY2xzLTEiIGQ9Ik01My4xNiwxMy4wOGg1LjQ2djQuMDJjLjkyLTEuNTIsMi4xNy0yLjY5LDMuNzUtMy41MSwxLjU4LS44MiwzLjMzLTEuMjMsNS4yNS0xLjIzLDIuMiwwLDQuMjEuNTMsNi4wMywxLjU5LDEuODIsMS4wNiwzLjIxLDIuNDksNC4xNyw0LjI5LDEuMDQtMS44OCwyLjQ1LTMuMzMsNC4yMy00LjM1LDEuNzgtMS4wMiwzLjc1LTEuNTMsNS45MS0xLjUzczQuMjIuNTMsNi4wNiwxLjU5YzEuODQsMS4wNiwzLjMsMi41MSw0LjM4LDQuMzUsMS4wOCwxLjg0LDEuNjIsMy45LDEuNjIsNi4xOHYyMC45NGgtNS42NHYtMTkuMTRjMC0yLjY0LS42OS00LjcyLTIuMDctNi4yNC0xLjM4LTEuNTItMy4xNy0yLjI4LTUuMzctMi4yOHMtNC4wNi43Ny01LjQ2LDIuMzFjLTEuNCwxLjU0LTIuMSwzLjYxLTIuMSw2LjIxdjE5LjE0aC01LjY0di0xOS4xNGMwLTIuNjQtLjY4LTQuNzItMi4wNC02LjI0LTEuMzYtMS41Mi0zLjE2LTIuMjgtNS40LTIuMjhzLTQuMDEuNzctNS40MywyLjMxYy0xLjQyLDEuNTQtMi4xMywzLjYxLTIuMTMsNi4yMXYxOS4xNGgtNS41OFYxMy4wOFoiLz4KICAgICAgPHBhdGggY2xhc3M9ImNscy0xIiBkPSJNMTA5LjAyLDQzLjU5Yy0yLTEuNy0zLTMuOTEtMy02LjYzcy44Ny00Ljg1LDIuNjEtNi42M2MxLjc0LTEuNzgsNC4zOS0yLjk3LDcuOTUtMy41N2wxMS4wNC0xLjh2LTEuNWMwLTEuNzYtLjY2LTMuMTktMS45OC00LjI5LTEuMzItMS4xLTMuMDItMS42NS01LjEtMS42NS0xLjg0LDAtMy40Ny40Ny00Ljg5LDEuNDEtMS40Mi45NC0yLjQ3LDIuMTktMy4xNSwzLjc1bC00Ljg2LTIuNTJjLjkyLTIuMjQsMi42LTQuMSw1LjA0LTUuNTgsMi40NC0xLjQ4LDUuMS0yLjIyLDcuOTgtMi4yMiwyLjQ0LDAsNC42MS40Nyw2LjUxLDEuNDEsMS45Ljk0LDMuMzgsMi4yNSw0LjQ0LDMuOTMsMS4wNiwxLjY4LDEuNTksMy42LDEuNTksNS43NnYyMS45NmgtNS40NnYtNC4yYy0xLjE2LDEuNTItMi42OSwyLjcyLTQuNTksMy42LTEuOS44OC00LjAxLDEuMzItNi4zMywxLjMyLTMuMiwwLTUuOC0uODUtNy44LTIuNTVaTTExMy40OSw0MC4wOGMxLjA2Ljg4LDIuMzksMS4zMiwzLjk5LDEuMzIsMS45NiwwLDMuNzEtLjQzLDUuMjUtMS4yOSwxLjU0LS44NiwyLjc0LTIuMDMsMy42LTMuNTEuODYtMS40OCwxLjI5LTMuMTIsMS4yOS00Ljkydi0yLjA0bC05Ljc4LDEuNjJjLTMuOTYuNjgtNS45NCwyLjUyLTUuOTQsNS41MiwwLDEuMzIuNTMsMi40MiwxLjU5LDMuM1oiLz4KICAgICAgPHBhdGggY2xhc3M9ImNscy0yIiBkPSJNMTUxLjUsNDQuNzNjLTEuOTYtLjk0LTMuNTYtMi4yNy00LjgtMy45OXY0LjY4aC01LjQ2VjBoNS41OHYxNy40NmMxLjI4LTEuNiwyLjg5LTIuODUsNC44My0zLjc1LDEuOTQtLjksNC4wNy0xLjM1LDYuMzktMS4zNSwzLDAsNS43Mi43NCw4LjE2LDIuMjIsMi40NCwxLjQ4LDQuMzYsMy41MSw1Ljc2LDYuMDksMS40LDIuNTgsMi4xLDUuNDUsMi4xLDguNjFzLS43LDYuMDItMi4xLDguNThjLTEuNCwyLjU2LTMuMzIsNC41OC01Ljc2LDYuMDYtMi40NCwxLjQ4LTUuMTgsMi4yMi04LjIyLDIuMjItMi4zNiwwLTQuNTItLjQ3LTYuNDgtMS40MVpNMTQ4LjIzLDM1LjEzYy45NCwxLjc0LDIuMjMsMy4xMSwzLjg3LDQuMTEsMS42NCwxLDMuNDYsMS41LDUuNDYsMS41czMuODEtLjUsNS40My0xLjVjMS42Mi0xLDIuODgtMi4zNywzLjc4LTQuMTEuOS0xLjc0LDEuMzUtMy42OSwxLjM1LTUuODVzLS40Ni00LjEyLTEuMzgtNS44OGMtLjkyLTEuNzYtMi4xOC0zLjE0LTMuNzgtNC4xNC0xLjYtMS0zLjQtMS41LTUuNC0xLjVzLTMuODIuNS01LjQ2LDEuNWMtMS42NCwxLTIuOTMsMi4zOC0zLjg3LDQuMTQtLjk0LDEuNzYtMS40MSwzLjcyLTEuNDEsNS44OHMuNDcsNC4xMSwxLjQxLDUuODVaIi8+CiAgICAgIDxwYXRoIGNsYXNzPSJjbHMtMiIgZD0iTTE4Ny43MSw0My45NWMtMi41NC0xLjQ2LTQuNTUtMy40OC02LjAzLTYuMDYtMS40OC0yLjU4LTIuMjItNS40Ny0yLjIyLTguNjdzLjczLTYuMDMsMi4xOS04LjYxYzEuNDYtMi41OCwzLjQ2LTQuNiw2LTYuMDYsMi41NC0xLjQ2LDUuMzctMi4xOSw4LjQ5LTIuMTlzNS44OS43Myw4LjQzLDIuMTljMi41NCwxLjQ2LDQuNTQsMy40Nyw2LDYuMDMsMS40NiwyLjU2LDIuMTksNS40NCwyLjE5LDguNjRzLS43NCw2LjE0LTIuMjIsOC43Yy0xLjQ4LDIuNTYtMy40OSw0LjU3LTYuMDMsNi4wMy0yLjU0LDEuNDYtNS4zMywyLjE5LTguMzcsMi4xOXMtNS44OS0uNzMtOC40My0yLjE5Wk0xODYuNzUsMzUuMWMuOTQsMS43NiwyLjI0LDMuMTQsMy45LDQuMTQsMS42NiwxLDMuNDksMS41LDUuNDksMS41czMuNzYtLjUsNS40LTEuNWMxLjY0LTEsMi45My0yLjM4LDMuODctNC4xNC45NC0xLjc2LDEuNDEtMy43MiwxLjQxLTUuODhzLS40Ny00LjExLTEuNDEtNS44NWMtLjk0LTEuNzQtMi4yMy0zLjExLTMuODctNC4xMS0xLjY0LTEtMy40NC0xLjUtNS40LTEuNXMtMy44My41LTUuNDksMS41Yy0xLjY2LDEtMi45NiwyLjM3LTMuOSw0LjExLS45NCwxLjc0LTEuNDEsMy42OS0xLjQxLDUuODVzLjQ3LDQuMTIsMS40MSw1Ljg4WiIvPgogICAgICA8cGF0aCBjbGFzcz0iY2xzLTIiIGQ9Ik0yMjUuOSwyOS4yMmwtMTEuMjgtMTYuMTRoNi43Mmw3LjkyLDExLjUyLDcuODYtMTEuNTJoNi43OGwtMTEuMjgsMTYuMTQsMTEuMjIsMTYuMmgtNi43MmwtNy44Ni0xMS41OC03Ljg2LDExLjU4aC02Ljg0bDExLjM0LTE2LjJaIi8+CiAgICA8L2c+CiAgPC9nPgo8L3N2Zz4=';  // white text — for dark header
$pimaboxLogoLight = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyBpZD0iRWJlbmVfMiIgZGF0YS1uYW1lPSJFYmVuZSAyIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNDMuOSA1Ny40MiI+CiAgPGRlZnM+CiAgICA8c3R5bGU+CiAgICAgIC5jbHMtMSB7CiAgICAgICAgZmlsbDogIzMzMzsKICAgICAgfQoKICAgICAgLmNscy0yIHsKICAgICAgICBmaWxsOiAjMDg5NDg4OwogICAgICB9CiAgICA8L3N0eWxlPgogIDwvZGVmcz4KICA8ZyBpZD0iRWJlbmVfMS0yIiBkYXRhLW5hbWU9IkViZW5lIDEiPgogICAgPGc+CiAgICAgIDxwYXRoIGNsYXNzPSJjbHMtMSIgZD0iTTAsMTMuMDhoNS40NnY0LjVjMS4yOC0xLjY0LDIuODktMi45Miw0LjgzLTMuODQsMS45NC0uOTIsNC4xMS0xLjM4LDYuNTEtMS4zOCwzLDAsNS43Mi43NCw4LjE2LDIuMjIsMi40NCwxLjQ4LDQuMzYsMy41MSw1Ljc2LDYuMDksMS40LDIuNTgsMi4xLDUuNDUsMi4xLDguNjFzLS43LDYuMDItMi4xLDguNThjLTEuNCwyLjU2LTMuMzIsNC41OC01Ljc2LDYuMDYtMi40NCwxLjQ4LTUuMTgsMi4yMi04LjIyLDIuMjItMi4zMiwwLTQuNDUtLjQ1LTYuMzktMS4zNS0xLjk0LS45LTMuNTMtMi4xOS00Ljc3LTMuODd2MTYuNUgwVjEzLjA4Wk02Ljk5LDM1LjEzYy45NCwxLjc0LDIuMjMsMy4xMSwzLjg3LDQuMTEsMS42NCwxLDMuNDYsMS41LDUuNDYsMS41czMuODEtLjUsNS40My0xLjVjMS42Mi0xLDIuODgtMi4zNywzLjc4LTQuMTEuOS0xLjc0LDEuMzUtMy42OSwxLjM1LTUuODVzLS40Ni00LjEyLTEuMzgtNS44OGMtLjkyLTEuNzYtMi4xOC0zLjE0LTMuNzgtNC4xNC0xLjYtMS0zLjQtMS41LTUuNC0xLjVzLTMuODIuNS01LjQ2LDEuNWMtMS42NCwxLTIuOTMsMi4zOC0zLjg3LDQuMTQtLjk0LDEuNzYtMS40MSwzLjcyLTEuNDEsNS44OHMuNDcsNC4xMSwxLjQxLDUuODVaIi8+CiAgICAgIDxwYXRoIGNsYXNzPSJjbHMtMSIgZD0iTTM5Ljg0LDIuNTljLjY0LS42NCwxLjQ4LS45NiwyLjUyLS45NnMxLjg4LjMyLDIuNTIuOTZjLjY0LjY0Ljk2LDEuNDguOTYsMi41MnMtLjMyLDEuODgtLjk2LDIuNTJjLS42NC42NC0xLjQ4Ljk2LTIuNTIuOTZzLTEuODgtLjMyLTIuNTItLjk2Yy0uNjQtLjY0LS45Ni0xLjQ4LS45Ni0yLjUycy4zMi0xLjg4Ljk2LTIuNTJaTTM5LjU0LDE4LjQyaDUuNTh2MjdoLTUuNTh2LTI3WiIvPgogICAgICA8cGF0aCBjbGFzcz0iY2xzLTEiIGQ9Ik01My4xNiwxMy4wOGg1LjQ2djQuMDJjLjkyLTEuNTIsMi4xNy0yLjY5LDMuNzUtMy41MSwxLjU4LS44MiwzLjMzLTEuMjMsNS4yNS0xLjIzLDIuMiwwLDQuMjEuNTMsNi4wMywxLjU5LDEuODIsMS4wNiwzLjIxLDIuNDksNC4xNyw0LjI5LDEuMDQtMS44OCwyLjQ1LTMuMzMsNC4yMy00LjM1LDEuNzgtMS4wMiwzLjc1LTEuNTMsNS45MS0xLjUzczQuMjIuNTMsNi4wNiwxLjU5YzEuODQsMS4wNiwzLjMsMi41MSw0LjM4LDQuMzUsMS4wOCwxLjg0LDEuNjIsMy45LDEuNjIsNi4xOHYyMC45NGgtNS42NHYtMTkuMTRjMC0yLjY0LS42OS00LjcyLTIuMDctNi4yNC0xLjM4LTEuNTItMy4xNy0yLjI4LTUuMzctMi4yOHMtNC4wNi43Ny01LjQ2LDIuMzFjLTEuNCwxLjU0LTIuMSwzLjYxLTIuMSw2LjIxdjE5LjE0aC01LjY0di0xOS4xNGMwLTIuNjQtLjY4LTQuNzItMi4wNC02LjI0LTEuMzYtMS41Mi0zLjE2LTIuMjgtNS40LTIuMjhzLTQuMDEuNzctNS40MywyLjMxYy0xLjQyLDEuNTQtMi4xMywzLjYxLTIuMTMsNi4yMXYxOS4xNGgtNS41OFYxMy4wOFoiLz4KICAgICAgPHBhdGggY2xhc3M9ImNscy0xIiBkPSJNMTA5LjAyLDQzLjU5Yy0yLTEuNy0zLTMuOTEtMy02LjYzcy44Ny00Ljg1LDIuNjEtNi42M2MxLjc0LTEuNzgsNC4zOS0yLjk3LDcuOTUtMy41N2wxMS4wNC0xLjh2LTEuNWMwLTEuNzYtLjY2LTMuMTktMS45OC00LjI5LTEuMzItMS4xLTMuMDItMS42NS01LjEtMS42NS0xLjg0LDAtMy40Ny40Ny00Ljg5LDEuNDEtMS40Mi45NC0yLjQ3LDIuMTktMy4xNSwzLjc1bC00Ljg2LTIuNTJjLjkyLTIuMjQsMi42LTQuMSw1LjA0LTUuNTgsMi40NC0xLjQ4LDUuMS0yLjIyLDcuOTgtMi4yMiwyLjQ0LDAsNC42MS40Nyw2LjUxLDEuNDEsMS45Ljk0LDMuMzgsMi4yNSw0LjQ0LDMuOTMsMS4wNiwxLjY4LDEuNTksMy42LDEuNTksNS43NnYyMS45NmgtNS40NnYtNC4yYy0xLjE2LDEuNTItMi42OSwyLjcyLTQuNTksMy42LTEuOS44OC00LjAxLDEuMzItNi4zMywxLjMyLTMuMiwwLTUuOC0uODUtNy44LTIuNTVaTTExMy40OSw0MC4wOGMxLjA2Ljg4LDIuMzksMS4zMiwzLjk5LDEuMzIsMS45NiwwLDMuNzEtLjQzLDUuMjUtMS4yOSwxLjU0LS44NiwyLjc0LTIuMDMsMy42LTMuNTEuODYtMS40OCwxLjI5LTMuMTIsMS4yOS00Ljkydi0yLjA0bC05Ljc4LDEuNjJjLTMuOTYuNjgtNS45NCwyLjUyLTUuOTQsNS41MiwwLDEuMzIuNTMsMi40MiwxLjU5LDMuM1oiLz4KICAgICAgPHBhdGggY2xhc3M9ImNscy0yIiBkPSJNMTUxLjUsNDQuNzNjLTEuOTYtLjk0LTMuNTYtMi4yNy00LjgtMy45OXY0LjY4aC01LjQ2VjBoNS41OHYxNy40NmMxLjI4LTEuNiwyLjg5LTIuODUsNC44My0zLjc1LDEuOTQtLjksNC4wNy0xLjM1LDYuMzktMS4zNSwzLDAsNS43Mi43NCw4LjE2LDIuMjIsMi40NCwxLjQ4LDQuMzYsMy41MSw1Ljc2LDYuMDksMS40LDIuNTgsMi4xLDUuNDUsMi4xLDguNjFzLS43LDYuMDItMi4xLDguNThjLTEuNCwyLjU2LTMuMzIsNC41OC01Ljc2LDYuMDYtMi40NCwxLjQ4LTUuMTgsMi4yMi04LjIyLDIuMjItMi4zNiwwLTQuNTItLjQ3LTYuNDgtMS40MVpNMTQ4LjIzLDM1LjEzYy45NCwxLjc0LDIuMjMsMy4xMSwzLjg3LDQuMTEsMS42NCwxLDMuNDYsMS41LDUuNDYsMS41czMuODEtLjUsNS40My0xLjVjMS42Mi0xLDIuODgtMi4zNywzLjc4LTQuMTEuOS0xLjc0LDEuMzUtMy42OSwxLjM1LTUuODVzLS40Ni00LjEyLTEuMzgtNS44OGMtLjkyLTEuNzYtMi4xOC0zLjE0LTMuNzgtNC4xNC0xLjYtMS0zLjQtMS41LTUuNC0xLjVzLTMuODIuNS01LjQ2LDEuNWMtMS42NCwxLTIuOTMsMi4zOC0zLjg3LDQuMTQtLjk0LDEuNzYtMS40MSwzLjcyLTEuNDEsNS44OHMuNDcsNC4xMSwxLjQxLDUuODVaIi8+CiAgICAgIDxwYXRoIGNsYXNzPSJjbHMtMiIgZD0iTTE4Ny43MSw0My45NWMtMi41NC0xLjQ2LTQuNTUtMy40OC02LjAzLTYuMDYtMS40OC0yLjU4LTIuMjItNS40Ny0yLjIyLTguNjdzLjczLTYuMDMsMi4xOS04LjYxYzEuNDYtMi41OCwzLjQ2LTQuNiw2LTYuMDYsMi41NC0xLjQ2LDUuMzctMi4xOSw4LjQ5LTIuMTlzNS44OS43Myw4LjQzLDIuMTljMi41NCwxLjQ2LDQuNTQsMy40Nyw2LDYuMDMsMS40NiwyLjU2LDIuMTksNS40NCwyLjE5LDguNjRzLS43NCw2LjE0LTIuMjIsOC43Yy0xLjQ4LDIuNTYtMy40OSw0LjU3LTYuMDMsNi4wMy0yLjU0LDEuNDYtNS4zMywyLjE5LTguMzcsMi4xOXMtNS44OS0uNzMtOC40My0yLjE5Wk0xODYuNzUsMzUuMWMuOTQsMS43NiwyLjI0LDMuMTQsMy45LDQuMTQsMS42NiwxLDMuNDksMS41LDUuNDksMS41czMuNzYtLjUsNS40LTEuNWMxLjY0LTEsMi45My0yLjM4LDMuODctNC4xNC45NC0xLjc2LDEuNDEtMy43MiwxLjQxLTUuODhzLS40Ny00LjExLTEuNDEtNS44NWMtLjk0LTEuNzQtMi4yMy0zLjExLTMuODctNC4xMS0xLjY0LTEtMy40NC0xLjUtNS40LTEuNXMtMy44My41LTUuNDksMS41Yy0xLjY2LDEtMi45NiwyLjM3LTMuOSw0LjExLS45NCwxLjc0LTEuNDEsMy42OS0xLjQxLDUuODVzLjQ3LDQuMTIsMS40MSw1Ljg4WiIvPgogICAgICA8cGF0aCBjbGFzcz0iY2xzLTIiIGQ9Ik0yMjUuOSwyOS4yMmwtMTEuMjgtMTYuMTRoNi43Mmw3LjkyLDExLjUyLDcuODYtMTEuNTJoNi43OGwtMTEuMjgsMTYuMTQsMTEuMjIsMTYuMmgtNi43MmwtNy44Ni0xMS41OC03Ljg2LDExLjU4aC02Ljg0bDExLjM0LTE2LjJaIi8+CiAgICA8L2c+CiAgPC9nPgo8L3N2Zz4='; // dark text — for light background


// ---- Language strings ----
$strings = [
    'en' => [
        'dashboard'        => 'Analytics Dashboard',
        'tagline'          => 'measure more. manage less.',
        'login_btn'        => 'Login',
        'wrong_password'   => '✗ Wrong password',
        'attempts_left'    => '%d attempt%s remaining before lockout.',
        'locked_out'       => 'Too many failed attempts. Try again in %s.',
        'summary'          => 'Your site had <strong>%s pageviews</strong> from <strong>%s visitors</strong> this month.',
        'total_views'      => 'Total Views',
        'all_time'         => 'all time',
        'today'            => 'Today',
        'visitors'         => 'visitors',
        'this_week'        => 'This Week',
        'this_month'       => 'This Month',
        'vs_last_week'     => 'vs last week',
        'vs_last_month'    => 'vs last month',
        'trend'            => '-Day Trend',
        'top_pages'        => 'Top Pages',
        'this_month_label' => 'this month',
        'change_label'     => 'This month · change vs. last month',
        'referrers'        => 'Referrers',
        'entry_pages'      => 'Entry Pages',
        'browser_lang'     => 'Browser Language',
        'time_of_day'      => 'Time of Day',
        'peak'             => 'Peak',
        'views'            => 'views',
        'device_type'      => 'Device Type',
        'top_countries'    => 'Top Countries',
        'recent_hits'      => 'Recent %d Hits',
        'show'             => 'Show ↓',
        'hide'             => 'Hide ↑',
        'no_data'          => 'No data yet — add the tracking snippet to your site to get started.',
        'no_pages'         => 'No data yet',
        'no_referrers'     => 'No referrers yet',
        'no_external'      => 'No external traffic yet',
        'no_lang'          => 'No data yet',
        'no_geo'           => 'Geo disabled or no data',
        'entry_note'       => 'First pages seen by visitors from external sources',
        'refresh'          => 'Refresh',
        'logout'           => 'Logout',
        'export'           => '↓ Export CSV',
        'cleared'          => 'All data cleared successfully.',
        'danger_title'     => '⚠ Danger Zone',
        'danger_desc'      => 'These actions are irreversible. Only visible because ADVANCED_MODE is enabled in config.php.',
        'clear_btn'        => 'Clear all data',
        'confirm_msg'      => 'This will permanently delete all %s rows. Are you sure?',
        'confirm_btn'      => 'Yes, delete everything',
        'powered_by'       => 'Powered by',
        'tip_trend'        => 'Daily pageviews over the last %d days.',
        'tip_pages'        => 'Most visited pages this month, with change vs. last month.',
        'tip_referrers'    => 'Where your visitors come from — which websites linked to yours.',
        'tip_entry'        => 'The first page visitors see when arriving from an external source like Google or another website.',
        'tip_lang'         => 'The language set in your visitors\' browsers — useful to understand your audience.',
        'tip_tod'          => 'When your visitors are most active. Hover over a bar to see the exact hour and view count.',
        'tip_device'       => 'Whether visitors are using a desktop, mobile phone, or tablet.',
        'tip_countries'    => 'Where your visitors are located, detected via their IP address. The IP itself is never stored.',
    ],
    'de' => [
        'dashboard'        => 'Analyse-Dashboard',
        'tagline'          => 'mehr messen. weniger verwalten.',
        'login_btn'        => 'Anmelden',
        'wrong_password'   => '✗ Falsches Passwort',
        'attempts_left'    => 'Noch %d Versuch%s bis zur Sperre.',
        'locked_out'       => 'Zu viele Fehlversuche. Bitte in %s erneut versuchen.',
        'summary'          => 'Deine Website hatte diesen Monat <strong>%s Seitenaufrufe</strong> von <strong>%s Besuchern</strong>.',
        'total_views'      => 'Seitenaufrufe',
        'all_time'         => 'gesamt',
        'today'            => 'Heute',
        'visitors'         => 'Besucher',
        'this_week'        => 'Diese Woche',
        'this_month'       => 'Dieser Monat',
        'vs_last_week'     => 'vs. letzte Woche',
        'vs_last_month'    => 'vs. letzter Monat',
        'trend'            => '-Tage-Verlauf',
        'top_pages'        => 'Meistbesuchte Seiten',
        'this_month_label' => 'diesen Monat',
        'change_label'     => 'Dieser Monat · Änderung vs. letzter Monat',
        'referrers'        => 'Quellen',
        'entry_pages'      => 'Einstiegsseiten',
        'browser_lang'     => 'Browsersprache',
        'time_of_day'      => 'Tageszeit',
        'peak'             => 'Spitzenzeit',
        'views'            => 'Aufrufe',
        'device_type'      => 'Gerätetyp',
        'top_countries'    => 'Länder',
        'recent_hits'      => 'Letzte %d Aufrufe',
        'show'             => 'Anzeigen ↓',
        'hide'             => 'Ausblenden ↑',
        'no_data'          => 'Noch keine Daten — füge das Tracking-Snippet auf deiner Website ein.',
        'no_pages'         => 'Noch keine Daten',
        'no_referrers'     => 'Noch keine Quellen',
        'no_external'      => 'Noch kein externer Traffic',
        'no_lang'          => 'Noch keine Daten',
        'no_geo'           => 'Geo deaktiviert oder keine Daten',
        'entry_note'       => 'Erste Seiten, die Besucher von externen Quellen sehen',
        'refresh'          => 'Aktualisieren',
        'logout'           => 'Abmelden',
        'export'           => '↓ CSV exportieren',
        'cleared'          => 'Alle Daten wurden erfolgreich gelöscht.',
        'danger_title'     => '⚠ Gefahrenzone',
        'danger_desc'      => 'Diese Aktionen sind unwiderruflich. Nur sichtbar weil ADVANCED_MODE in config.php aktiviert ist.',
        'clear_btn'        => 'Alle Daten löschen',
        'confirm_msg'      => 'Dadurch werden alle %s Einträge dauerhaft gelöscht. Bist du sicher?',
        'confirm_btn'      => 'Ja, alles löschen',
        'powered_by'       => 'Erstellt mit',
        'tip_trend'        => 'Tägliche Seitenaufrufe der letzten %d Tage.',
        'tip_pages'        => 'Meistbesuchte Seiten diesen Monat, mit Änderung vs. letzten Monat.',
        'tip_referrers'    => 'Woher deine Besucher kommen — welche Websites auf deine verlinkt haben.',
        'tip_entry'        => 'Die erste Seite, die Besucher sehen, wenn sie von einer externen Quelle wie Google kommen.',
        'tip_lang'         => 'Die in den Browsern deiner Besucher eingestellte Sprache.',
        'tip_tod'          => 'Wann deine Besucher am aktivsten sind. Fahre über einen Balken für Details.',
        'tip_device'       => 'Ob Besucher ein Desktop-Gerät, Mobiltelefon oder Tablet verwenden.',
        'tip_countries'    => 'Woher deine Besucher stammen, ermittelt über ihre IP-Adresse. Die IP selbst wird nie gespeichert.',
    ],
];
$lang = defined('LANG') ? LANG : 'en';
$t = $strings[$lang] ?? $strings['en'];

// ---- DB helper ----
function openDb(): ?SQLite3 {
    if (!file_exists(DB_PATH)) return null;
    try {
        $db = new SQLite3(DB_PATH, SQLITE3_OPEN_READONLY);
        $db->enableExceptions(true);
        $db->busyTimeout(3000);
        return $db;
    } catch (Exception $e) {
        return null;
    }
}

// ---- CSV Export ----
if ($authed && isset($_GET['export'])) {
    $db = openDb();
    if ($db) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="pimabox-export-' . date('Y-m-d') . '.csv"');
        echo "date,time,page,referrer,device,country\n";
        $res = $db->query('SELECT date,time,page,referrer,device,country FROM hits ORDER BY date DESC, time DESC');
        while ($row = $res->fetchArray(SQLITE3_NUM)) {
            echo implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
        }
        $db->close();
    }
    exit;
}

// ---- Clear all data (Advanced Mode only) ----
if ($authed && $advancedMode && isset($_POST['clear_data']) && ($_POST['confirm_clear'] ?? '') === 'yes') {
    try {
        $db = new SQLite3(DB_PATH);
        $db->exec('DELETE FROM hits');
        $db->exec('VACUUM');
        $db->close();
        $clearSuccess = true;
    } catch (Exception $e) {
        $clearError = true;
    }
}

// ---- Data ----
$stats = [
    'total'       => 0,
    'today'       => 0,
    'uniq_today'  => 0,
    'uniq_total'  => 0,
    'uniq_month'  => 0,
    'this_week'   => 0,
    'last_week'   => 0,
    'this_month'  => 0,
    'last_month'  => 0,
    'pages'       => [],
    'pages_prev'  => [],
    'referrers'   => [],
    'devices'     => ['desktop' => 0, 'mobile' => 0, 'tablet' => 0],
    'countries'   => [],
    'trend'       => [],
    'hours'       => array_fill(0, 24, ['views' => 0, 'uniq' => []]),
    'languages'   => [],
    'entry_pages' => [],
    'recent'      => [],
    'db_size'     => 0,
    'db_rows'     => 0,
];

if ($authed) {
    $db = openDb();
    if ($db) {
        $today      = date('Y-m-d');
        $trendFrom  = date('Y-m-d', strtotime('-' . (TREND_DAYS - 1) . ' days'));
        $weekStart  = date('Y-m-d', strtotime('Monday this week'));
        $prevStart  = date('Y-m-d', strtotime('Monday last week'));
        // Fair comparison: same number of days as current week so far
        $currentWeekDay = (int) date('N') - 1; // 0=Mon, 6=Sun
        $prevEnd    = date('Y-m-d', strtotime('Monday last week + ' . $currentWeekDay . ' days'));
        $monthStart = date('Y-m-01');
        $lastMStart = date('Y-m-01', strtotime('first day of last month'));
        // Compare same number of days as current month (fair comparison)
        $currentDay = (int) date('j');
        $lastMEnd   = date('Y-m-', strtotime('first day of last month')) . sprintf('%02d', $currentDay);

        $stats['total']      = (int) $db->querySingle('SELECT COUNT(*) FROM hits');
        $stats['today']      = (int) $db->querySingle("SELECT COUNT(*) FROM hits WHERE date = '$today'");
        $stats['uniq_today'] = (int) $db->querySingle("SELECT COUNT(DISTINCT vid) FROM hits WHERE date = '$today' AND vid != ''");
        $stats['uniq_total'] = (int) $db->querySingle("SELECT COUNT(DISTINCT vid) FROM hits WHERE vid != ''");
        $stats['uniq_month'] = (int) $db->querySingle("SELECT COUNT(DISTINCT vid) FROM hits WHERE date >= '$monthStart' AND vid != ''");
        $stats['this_week']  = (int) $db->querySingle("SELECT COUNT(*) FROM hits WHERE date >= '$weekStart'");
        $stats['last_week']  = (int) $db->querySingle("SELECT COUNT(*) FROM hits WHERE date >= '$prevStart' AND date <= '$prevEnd'");
        $stats['this_month'] = (int) $db->querySingle("SELECT COUNT(*) FROM hits WHERE date >= '$monthStart'");
        $stats['last_month'] = (int) $db->querySingle("SELECT COUNT(*) FROM hits WHERE date >= '$lastMStart' AND date <= '$lastMEnd'");

        // Top pages (this month)
        $res = $db->query("SELECT page, COUNT(*) as c FROM hits WHERE date >= '$monthStart' GROUP BY page ORDER BY c DESC LIMIT 8");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $stats['pages'][$row['page']] = $row['c'];
        }

        // Top pages prev month (same days as current month)
        $res = $db->query("SELECT page, COUNT(*) as c FROM hits WHERE date >= '$lastMStart' AND date <= '$lastMEnd' GROUP BY page ORDER BY c DESC LIMIT 8");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $stats['pages_prev'][$row['page']] = $row['c'];
        }

        // Referrers
        $res = $db->query("SELECT referrer, COUNT(*) as c FROM hits WHERE referrer != '' GROUP BY referrer ORDER BY c DESC LIMIT 8");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $ref = preg_replace('/^www\./', '', parse_url($row['referrer'], PHP_URL_HOST) ?: $row['referrer']);
            $stats['referrers'][$ref] = ($stats['referrers'][$ref] ?? 0) + $row['c'];
        }
        arsort($stats['referrers']);

        // Devices
        $res = $db->query("SELECT device, COUNT(*) as c FROM hits GROUP BY device");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $dev = $row['device'] ?: 'desktop';
            $stats['devices'][$dev] = ($stats['devices'][$dev] ?? 0) + $row['c'];
        }

        // Countries
        $res = $db->query("SELECT country, COUNT(*) as c FROM hits WHERE country != '' GROUP BY country ORDER BY c DESC LIMIT 8");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $stats['countries'][$row['country']] = $row['c'];
        }

        // Trend
        for ($i = TREND_DAYS - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $stats['trend'][$d] = ['views' => 0, 'uniq' => 0];
        }
        $res = $db->query("SELECT date, COUNT(*) as views, COUNT(DISTINCT vid) as uniq FROM hits WHERE date >= '$trendFrom' GROUP BY date");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            if (isset($stats['trend'][$row['date']])) {
                $stats['trend'][$row['date']] = ['views' => $row['views'], 'uniq' => $row['uniq']];
            }
        }

        // Hours
        $res = $db->query("SELECT CAST(substr(time,1,2) AS INTEGER) as h, COUNT(*) as views, COUNT(DISTINCT vid) as uniq FROM hits GROUP BY h");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $h = (int) $row['h'];
            if ($h >= 0 && $h < 24) {
                $stats['hours'][$h] = ['views' => $row['views'], 'uniq' => $row['uniq']];
            }
        }

        // Browser languages
        $res = $db->query("SELECT lang, COUNT(*) as c FROM hits WHERE lang IS NOT NULL AND lang != '' GROUP BY lang ORDER BY c DESC LIMIT 8");
        if ($res) {
            while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                $stats['languages'][$row['lang']] = $row['c'];
            }
        }

        // Entry pages (hits where referrer is external)
        $currentHost = SQLite3::escapeString($_SERVER['HTTP_HOST'] ?? '');
        $res = $db->query("SELECT page, COUNT(*) as c FROM hits WHERE referrer != '' AND referrer NOT LIKE '%{$currentHost}%' GROUP BY page ORDER BY c DESC LIMIT 8");
        if ($res) {
            while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                $stats['entry_pages'][$row['page']] = $row['c'];
            }
        }

        // Recent hits
        $res = $db->query("SELECT date,time,page,referrer,device,country FROM hits ORDER BY id DESC LIMIT " . RECENT_ENTRIES);
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $stats['recent'][] = $row;
        }

        $stats['db_size'] = file_exists(DB_PATH) ? round(filesize(DB_PATH) / 1024, 1) : 0;
        $stats['db_rows'] = $stats['total'];

        $db->close();
    }
}

$countryNames = [
    'AF'=>'Afghanistan','AL'=>'Albania','DZ'=>'Algeria','AR'=>'Argentina','AU'=>'Australia',
    'AT'=>'Austria','BE'=>'Belgium','BR'=>'Brazil','BG'=>'Bulgaria','CA'=>'Canada',
    'CL'=>'Chile','CN'=>'China','CO'=>'Colombia','HR'=>'Croatia','CZ'=>'Czechia',
    'DK'=>'Denmark','EG'=>'Egypt','FI'=>'Finland','FR'=>'France','DE'=>'Germany',
    'GR'=>'Greece','HK'=>'Hong Kong','HU'=>'Hungary','IN'=>'India','ID'=>'Indonesia',
    'IE'=>'Ireland','IL'=>'Israel','IT'=>'Italy','JP'=>'Japan','KZ'=>'Kazakhstan',
    'KE'=>'Kenya','KR'=>'South Korea','MY'=>'Malaysia','MX'=>'Mexico','MA'=>'Morocco',
    'NL'=>'Netherlands','NZ'=>'New Zealand','NG'=>'Nigeria','NO'=>'Norway','PK'=>'Pakistan',
    'PH'=>'Philippines','PL'=>'Poland','PT'=>'Portugal','RO'=>'Romania','RU'=>'Russia',
    'SA'=>'Saudi Arabia','RS'=>'Serbia','SG'=>'Singapore','SK'=>'Slovakia','ZA'=>'South Africa',
    'ES'=>'Spain','SE'=>'Sweden','CH'=>'Switzerland','TW'=>'Taiwan','TH'=>'Thailand',
    'TR'=>'Turkey','UA'=>'Ukraine','AE'=>'UAE','GB'=>'United Kingdom','US'=>'United States',
    'VN'=>'Vietnam','local'=>'Local',
];
function countryName(string $code, array $map): string {
    return $map[$code] ?? $code;
}

$langNames = [
    'af'=>'Afrikaans','ar'=>'Arabic','bg'=>'Bulgarian','bn'=>'Bengali',
    'cs'=>'Czech','da'=>'Danish','de'=>'German','el'=>'Greek',
    'en'=>'English','es'=>'Spanish','et'=>'Estonian','fa'=>'Persian',
    'fi'=>'Finnish','fr'=>'French','gu'=>'Gujarati','he'=>'Hebrew',
    'hi'=>'Hindi','hr'=>'Croatian','hu'=>'Hungarian','hy'=>'Armenian',
    'id'=>'Indonesian','it'=>'Italian','ja'=>'Japanese','ka'=>'Georgian',
    'kn'=>'Kannada','ko'=>'Korean','lt'=>'Lithuanian','lv'=>'Latvian',
    'mk'=>'Macedonian','ml'=>'Malayalam','mr'=>'Marathi','ms'=>'Malay',
    'nl'=>'Dutch','no'=>'Norwegian','pl'=>'Polish','pt'=>'Portuguese',
    'ro'=>'Romanian','ru'=>'Russian','sk'=>'Slovak','sl'=>'Slovenian',
    'sq'=>'Albanian','sr'=>'Serbian','sv'=>'Swedish','sw'=>'Swahili',
    'ta'=>'Tamil','te'=>'Telugu','th'=>'Thai','tr'=>'Turkish',
    'uk'=>'Ukrainian','ur'=>'Urdu','vi'=>'Vietnamese',
    'zh'=>'Chinese','zu'=>'Zulu',
];
function langName(string $code, array $map): string {
    return $map[strtolower($code)] ?? strtoupper($code);
}

$trendMax = 1;
foreach ($stats['trend'] as $trendItem) $trendMax = max($trendMax, $trendItem['views']);
$hourMax  = max(array_merge([1], array_column($stats['hours'], 'views')));
$devTotal = array_sum($stats['devices']);

$lockRemaining = '';
if ($isLocked) {
    $secs = $_SESSION['locked_until'] - time();
    $lockRemaining = $secs > 60 ? round($secs/60).' min' : $secs.' sec';
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($brandName) ?> · Analytics · <?= date("d M Y") ?></title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --accent:  <?= htmlspecialchars($brandColor) ?>;
    --bg:      #f5f7fa;
    --surface: #ffffff;
    --border:  #e2e6ed;
    --text:    #1a202c;
    --muted:   #718096;
    --danger:  #c0392b;
    --font:    <?= htmlspecialchars($brandFont) ?>;
    --sans:    system-ui, -apple-system, sans-serif;
  }
  body { font-family: var(--sans); background: var(--bg); color: var(--text); min-height: 100vh; }

  .login-wrap { display:flex; align-items:center; justify-content:center; min-height:100vh; padding:1rem; }
  .login-box { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:2.5rem 2rem; width:100%; max-width:360px; box-shadow:0 4px 24px rgba(44,36,22,.07); }
  .login-logo { margin-bottom:1.5rem; }
  .login-logo img { max-height:36px; max-width:160px; }
  .login-box h1 { font-family:var(--font); font-size:1.5rem; font-weight:700; margin-bottom:.25rem; }
  .login-box .sub { font-size:.78rem; color:var(--muted); margin-bottom:.4rem; text-transform:uppercase; letter-spacing:.05em; }
  .login-box .tagline { font-size:.78rem; color:var(--muted); margin-bottom:1.5rem; font-style:italic; }
  .login-box input[type=password] { width:100%; padding:.7rem 1rem; border:1px solid var(--border); border-radius:10px; font-size:.95rem; background:var(--bg); color:var(--text); outline:none; transition:border-color .15s; }
  .login-box input[type=password]:focus { border-color:var(--accent); }
  .login-box input:disabled, .login-box button:disabled { opacity:.4; cursor:not-allowed; }
  .login-box button { margin-top:.6rem; width:100%; padding:.75rem; background:var(--accent); color:#fff; border:none; border-radius:10px; font-size:.95rem; font-weight:500; cursor:pointer; transition:opacity .15s; }
  .login-box button:hover:not(:disabled) { opacity:.88; }
  .login-error { margin-top:.6rem; font-size:.8rem; color:var(--accent); }
  .login-locked { margin-top:.6rem; font-size:.8rem; color:#888; background:#f5f0eb; padding:.5rem .75rem; border-radius:6px; }
  .attempts-hint { font-size:.72rem; color:var(--muted); margin-top:.4rem; }

  header { background:#0d2d2b; border-bottom:1px solid #1a4a47; padding:1rem 1.5rem; display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .header-pimabox { display:flex; flex-direction:column; justify-content:center; }
  .header-pimabox-name { font-size:1rem; font-weight:700; color:#fff; letter-spacing:.02em; }
  .header-tagline { font-size:.65rem; color:#5eada8; letter-spacing:.04em; margin-top:.1rem; }
  .header-actions { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }
  .btn-ghost { background:none; border:1px solid #1a4a47; border-radius:8px; padding:.3rem .75rem; font-size:.78rem; color:#5eada8; cursor:pointer; transition:all .15s; text-decoration:none; display:inline-block; }
  .btn-ghost:hover { border-color:#fff; color:#fff; }
  .btn-export { background:var(--accent); color:#fff; border:none; border-radius:8px; padding:.3rem .75rem; font-size:.78rem; cursor:pointer; text-decoration:none; display:inline-block; transition:opacity .15s; }
  .btn-export:hover { opacity:.85; }

  .summary { padding:1.75rem 0 .5rem; }
  .summary-sentence { font-size:1.1rem; color:var(--text); line-height:1.6; }
  .summary-sentence strong { color:var(--accent); }
  .summary-date { font-size:.78rem; color:var(--muted); margin-top:.25rem; }

  main { max-width:1160px; margin:0 auto; padding:2rem 1.5rem; }

  .kpi-row { display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:14px; margin-bottom:1.5rem; }
  .kpi { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:1.25rem 1.25rem 1rem; }
  .kpi.highlight { border-left:3px solid var(--accent); }
  .kpi-label { font-size:.65rem; text-transform:uppercase; letter-spacing:.12em; color:var(--accent); margin-bottom:.5rem; }
  .kpi-value { font-family:var(--font); font-size:1.9rem; font-weight:700; line-height:1; }
  .kpi-sub { font-size:.72rem; color:var(--muted); margin-top:.3rem; }
  .kpi-delta { font-size:.72rem; margin-top:.3rem; font-weight:500; }
  .kpi-delta.up   { color:#2d6a4f; }
  .kpi-delta.down { color:#c0392b; }
  .kpi-delta.same { color:var(--muted); }

  /* Delta pills */
  .pill { display:inline-flex; align-items:center; gap:.25rem; padding:.2rem .55rem; border-radius:20px; font-size:.72rem; font-weight:600; margin-top:.4rem; }
  .pill.up   { background:#e8f5e9; color:#2d6a4f; }
  .pill.down { background:#fdecea; color:#c0392b; }
  .pill.same { background:var(--bg); color:var(--muted); }
  .pill svg  { width:10px; height:10px; flex-shrink:0; }

  /* Rank delta pills */
  .delta-pill { display:inline-flex; align-items:center; gap:.2rem; padding:.15rem .45rem; border-radius:20px; font-size:.65rem; font-weight:600; flex-shrink:0; }
  .delta-pill.up   { background:#e8f5e9; color:#2d6a4f; }
  .delta-pill.down { background:#fdecea; color:#c0392b; }
  .delta-pill.same { background:var(--bg); color:var(--muted); }

  .card { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:1.5rem; margin-bottom:14px; }
  .card h2 { font-family:var(--font); font-size:.95rem; font-weight:700; margin-bottom:1rem; padding-bottom:.75rem; border-bottom:1px solid var(--border); }
  .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px; }
  .grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-bottom:14px; }
  @media (max-width:900px) { .grid-3 { grid-template-columns:1fr 1fr; } }
  @media (max-width:640px) { .grid-2, .grid-3 { grid-template-columns:1fr; } }

  .trend-chart { display:flex; align-items:flex-end; gap:3px; height:80px; }
  .bar-col { flex:1; display:flex; flex-direction:column; align-items:center; height:100%; justify-content:flex-end; gap:3px; cursor:default; }
  .bar-views { width:100%; background:var(--accent); border-radius:3px 3px 0 0; min-height:2px; opacity:.75; transition:opacity .15s; }
  .bar-col:hover .bar-views { opacity:1; }
  .bar-col .lbl { font-size:.52rem; color:var(--muted); text-align:center; }

  .hours-chart { display:flex; align-items:flex-end; gap:2px; height:60px; }
  .hour-col { flex:1; display:flex; flex-direction:column; align-items:center; height:100%; justify-content:flex-end; gap:2px; }
  .hour-col .bar { width:100%; background:var(--accent); opacity:.65; border-radius:2px 2px 0 0; min-height:2px; transition:opacity .15s; }
  .hour-col:hover .bar { opacity:1; }
  .hour-col .lbl { font-size:.48rem; color:var(--muted); }

  .rank-list { list-style:none; }
  .rank-list li { display:flex; align-items:center; gap:.6rem; padding:.45rem 0; border-bottom:1px solid var(--border); font-size:.82rem; }
  .rank-list li:last-child { border-bottom:none; }
  .rank-n { font-size:.68rem; color:var(--muted); width:1.2rem; text-align:right; flex-shrink:0; }
  .rank-label { flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .rank-track { width:60px; height:5px; background:var(--bg); border-radius:3px; overflow:hidden; flex-shrink:0; }
  .rank-fill { height:100%; background:var(--accent); opacity:.6; border-radius:3px; }
  .rank-count { font-size:.8rem; font-weight:600; width:2.2rem; text-align:right; flex-shrink:0; }
  /* delta replaced by delta-pill */

  .dev-row { display:flex; align-items:center; gap:.75rem; padding:.4rem 0; }
  .dev-label { font-size:.8rem; width:4.5rem; flex-shrink:0; }
  .dev-track { flex:1; height:7px; background:var(--bg); border-radius:4px; overflow:hidden; }
  .dev-fill { height:100%; background:var(--accent); border-radius:4px; }
  .dev-fill.mobile { opacity:.7; }
  .dev-fill.tablet { opacity:.45; }
  .dev-pct { font-size:.75rem; color:var(--muted); width:2.5rem; text-align:right; flex-shrink:0; }

  .tbl-wrap { overflow-x:auto; }
  table { width:100%; border-collapse:collapse; font-size:.8rem; }
  thead th { text-align:left; padding:.5rem .75rem; font-size:.65rem; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); border-bottom:2px solid var(--border); background:var(--bg); white-space:nowrap; }
  tbody tr:hover { background:var(--bg); }
  tbody td { padding:.42rem .75rem; border-bottom:1px solid var(--border); max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  tbody tr:last-child td { border-bottom:none; }
  .muted { color:var(--muted); }
  .badge { display:inline-block; padding:.15rem .5rem; border-radius:20px; font-size:.65rem; text-transform:uppercase; letter-spacing:.05em; }
  .badge-desktop { background:rgba(13,148,136,.1); color:var(--accent); }
  .badge-mobile  { background:rgba(13,148,136,.07); color:var(--accent); }
  .badge-tablet  { background:rgba(13,148,136,.05); color:var(--accent); }

  .danger-zone { border:1px solid #f5c6c2; border-radius:14px; padding:1.5rem; margin-bottom:14px; background:#fff9f9; }
  .danger-zone h2 { font-family:var(--font); font-size:.95rem; font-weight:700; color:var(--danger); margin-bottom:.5rem; }
  .danger-zone p { font-size:.82rem; color:var(--muted); margin-bottom:1rem; }
  .danger-zone .db-info { font-size:.78rem; color:var(--muted); margin-bottom:1rem; font-family:monospace; background:#f5f0eb; padding:.5rem .75rem; border-radius:6px; }
  .btn-danger { background:var(--danger); color:#fff; border:none; border-radius:8px; padding:.5rem 1rem; font-size:.82rem; cursor:pointer; transition:opacity .15s; }
  .btn-danger:hover { opacity:.85; }
  .confirm-box { margin-top:.75rem; display:none; }
  .confirm-box.visible { display:block; }
  .confirm-box p { font-size:.8rem; color:var(--danger); margin-bottom:.5rem; }

  .alert-success { background:#e8f5e9; border:1px solid #a5d6a7; color:#2d6a4f; padding:.6rem 1rem; border-radius:8px; font-size:.82rem; margin-bottom:1rem; }
  .file-info { font-size:.72rem; color:var(--muted); margin-top:.75rem; }
  .section-note { font-size:.72rem; color:var(--muted); margin-top:.5rem; }
  .empty { text-align:center; padding:2.5rem; color:var(--muted); font-size:.85rem; }
  .no-data { color:var(--muted); font-size:.82rem; padding:.5rem 0; }

  /* Info tooltips */
  .card-title { display:flex; align-items:center; gap:.5rem; overflow:visible; }
  .card h2 { overflow:visible; }
  .info-btn {
    display:inline-flex; align-items:center; justify-content:center;
    width:12px; height:12px; border-radius:50%;
    border:1px solid var(--border); color:var(--muted);
    font-size:.5rem; font-weight:700; cursor:default;
    position:relative; flex-shrink:0; font-style:normal;
    line-height:1; font-family:var(--sans); opacity:.6;
  }
  .info-btn:hover { opacity:1; border-color:var(--muted); }
  .info-btn::after {
    content: attr(data-tip);
    position:absolute; bottom:calc(100% + 6px); left:50%;
    transform:translateX(-50%);
    background:var(--text); color:#fff;
    font-size:.72rem; font-weight:400; font-style:normal;
    padding:.4rem .6rem; border-radius:6px;
    white-space:nowrap; max-width:220px; white-space:normal;
    width:max-content; max-width:200px;
    pointer-events:none; opacity:0; transition:opacity .15s;
    z-index:10; line-height:1.4;
  }
  .info-btn:hover::after { opacity:1; }
</style>
</head>
<body>

<?php if (!$authed): ?>
<div class="login-wrap">
  <div class="login-box">
    <?php if ($brandLogo): ?>
      <div class="login-logo"><img src="<?= htmlspecialchars($brandLogo) ?>" alt="<?= htmlspecialchars($brandName) ?>"></div>
    <?php endif; ?>
    <img src="<?= $pimaboxLogoLight ?>" alt="pimabox" style="height:36px;width:auto;margin-bottom:1rem;display:block;">
    <div class="sub"><?= $t['dashboard'] ?></div>
    <div class="tagline"><?= $t['tagline'] ?></div>
    <form method="POST">
      <input type="password" name="password" placeholder="Password" autofocus autocomplete="current-password" <?= $isLocked ? 'disabled' : '' ?>>
      <button type="submit" <?= $isLocked ? 'disabled' : '' ?>><?= $t['login_btn'] ?></button>
      <?php if ($isLocked): ?>
        <p class="login-locked"><?= sprintf($t['locked_out'], $lockRemaining) ?></p>
      <?php elseif (!empty($authError)): ?>
        <p class="login-error"><?= $t['wrong_password'] ?></p>
        <?php $rem = $maxAttempts - $attempts; if ($rem <= 2 && $rem > 0): ?>
          <p class="attempts-hint"><?= $rem ?> attempt<?= $rem === 1 ? '' : 's' ?> remaining before lockout.</p>
        <?php endif; ?>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php else: ?>

<header>
  <div class="header-pimabox">
    <img src="<?= $pimaboxLogoDark ?>" alt="pimabox" style="height:28px;width:auto;display:block;">
    <div class="header-tagline">measure more. manage less.</div>
  </div>
  <div class="header-actions">
    <a href="?export=1" class="btn-export"><?= $t['export'] ?></a>
    <a href="?" class="btn-ghost"><?= $t['refresh'] ?></a>
    <a href="?logout=1" class="btn-ghost"><?= $t['logout'] ?></a>
  </div>
</header>

<main>

<?php if (!empty($clearSuccess)): ?>
  <div class="alert-success"><?= $t['cleared'] ?></div>
<?php endif; ?>

<?php if ($stats['total'] === 0): ?>
  <div class="card"><div class="empty"><?= $t['no_data'] ?></div></div>
<?php else: ?>

<?php
  $weekDiff   = $stats['this_week']  - $stats['last_week'];
  $monthDiff  = $stats['this_month'] - $stats['last_month'];
  $weekDelta  = $weekDiff  > 0 ? '+' . $weekDiff  : ($weekDiff  < 0 ? (string)$weekDiff  : '—');
  $monthDelta = $monthDiff > 0 ? '+' . $monthDiff : ($monthDiff < 0 ? (string)$monthDiff : '—');
  $weekClass  = $weekDiff  > 0 ? 'up' : ($weekDiff  < 0 ? 'down' : 'same');
  $monthClass = $monthDiff > 0 ? 'up' : ($monthDiff < 0 ? 'down' : 'same');
?>

<?php if ($brandLogo || (!empty($brandName) && $brandName !== 'pimabox')): ?>
<div style="text-align:center;padding:1.5rem 0 .5rem;margin-bottom:1rem;">
  <?php if ($brandLogo): ?>
    <img src="<?= htmlspecialchars($brandLogo) ?>" alt="<?= htmlspecialchars($brandName) ?>" style="max-height:80px;max-width:320px;">
  <?php else: ?>
    <span style="font-size:1.1rem;font-weight:600;color:var(--text);"><?= htmlspecialchars($brandName) ?></span>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="summary">
  <div class="summary-sentence">
    <?= sprintf($t['summary'], number_format($stats['this_month']), number_format($stats['uniq_month'])) ?>
  </div>
  <div class="summary-date"><?= date('d. F Y') ?> · <?= TIMEZONE ?></div>
</div>

<?php
  $arrowUp   = '<svg viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 8V2M5 2L2 5M5 2L8 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  $arrowDown = '<svg viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 2V8M5 8L2 5M5 8L8 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

  $weekArrow  = $weekDiff  > 0 ? $arrowUp : ($weekDiff  < 0 ? $arrowDown : '');
  $monthArrow = $monthDiff > 0 ? $arrowUp : ($monthDiff < 0 ? $arrowDown : '');
?>
<div class="kpi-row">
  <div class="kpi highlight">
    <div class="kpi-label"><?= $t['total_views'] ?></div>
    <div class="kpi-value"><?= number_format($stats['total']) ?></div>
    <div style="font-size:.82rem;color:var(--accent);opacity:.7;margin-top:.3rem;font-weight:500;"><?= number_format($stats['uniq_total']) ?> <?= $t['visitors'] ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-label"><?= $t['today'] ?></div>
    <div class="kpi-value"><?= number_format($stats['today']) ?></div>
    <div style="font-size:.82rem;color:var(--accent);opacity:.7;margin-top:.3rem;font-weight:500;"><?= number_format($stats['uniq_today']) ?> <?= $t['visitors'] ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-label"><?= $t['this_week'] ?></div>
    <div class="kpi-value"><?= number_format($stats['this_week']) ?></div>
    <div class="pill <?= $weekClass ?>"><?= $weekArrow ?><?= $weekDelta ?> <?= $t['vs_last_week'] ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-label"><?= $t['this_month'] ?></div>
    <div class="kpi-value"><?= number_format($stats['this_month']) ?></div>
    <div class="pill <?= $monthClass ?>"><?= $monthArrow ?><?= $monthDelta ?> <?= $t['vs_last_month'] ?></div>
  </div>
</div>

<div class="card">
  <h2><?= TREND_DAYS ?><?= $t['trend'] ?></h2>
  <div class="trend-chart">
    <?php foreach ($stats['trend'] as $d => $trendItem):
      $hv = $trendMax > 0 ? max(2, round($trendItem['views'] / $trendMax * 100)) : 2;
    ?>
    <div class="bar-col" title="<?= $d ?>&#10;Views: <?= $trendItem['views'] ?>&#10;Visitors: <?= $trendItem['uniq'] ?>">
      <div class="bar-views" style="height:<?= $hv ?>%"></div>
      <div class="lbl">
        <span style="display:block"><?= date('d', strtotime($d)) ?></span>
        <span style="display:block;font-size:.45rem;color:var(--muted)"><?= date('M', strtotime($d)) ?></span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <h2><span class="card-title"><?= $t['top_pages'] ?> <span style="font-size:.72rem;font-weight:400;color:var(--muted);"><?= $t['this_month_label'] ?></span> <i class="info-btn" data-tip="<?= htmlspecialchars($t['tip_pages']) ?>">i</i></span></h2>
    <?php if (empty($stats['pages'])): ?>
      <p class="no-data"><?= $t['no_pages'] ?></p>
    <?php else:
      $maxP = max($stats['pages']); $i = 1; ?>
      <ul class="rank-list">
      <?php foreach ($stats['pages'] as $p => $c):
        $prev   = $stats['pages_prev'][$p] ?? 0;
        $diff   = $c - $prev;
        $dClass = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'same');
        $dLabel = $diff > 0 ? '+' . $diff : ($diff < 0 ? (string)$diff : '—');
      ?>
        <li>
          <span class="rank-n"><?= $i++ ?></span>
          <span class="rank-label" title="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></span>
          <div class="rank-track"><div class="rank-fill" style="width:<?= round($c/$maxP*100) ?>%"></div></div>
          <span class="rank-count"><?= $c ?></span>
          <span class="delta-pill <?= $dClass ?>"><?= $dLabel ?></span>
        </li>
      <?php endforeach; ?>
      </ul>
      <p class="section-note">This month · change vs. last month</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2><span class="card-title"><?= $t['referrers'] ?> <i class="info-btn" data-tip="<?= htmlspecialchars($t['tip_referrers']) ?>">i</i></span></h2>
    <?php if (empty($stats['referrers'])): ?>
      <p class="no-data"><?= $t['no_referrers'] ?></p>
    <?php else:
      $maxR = max($stats['referrers']); $i = 1; ?>
      <ul class="rank-list">
      <?php foreach ($stats['referrers'] as $r => $c): ?>
        <li>
          <span class="rank-n"><?= $i++ ?></span>
          <span class="rank-label" title="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></span>
          <div class="rank-track"><div class="rank-fill" style="width:<?= round($c/$maxR*100) ?>%"></div></div>
          <span class="rank-count"><?= $c ?></span>
        </li>
      <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <h2><span class="card-title"><?= $t['entry_pages'] ?> <i class="info-btn" data-tip="<?= htmlspecialchars($t['tip_entry']) ?>">i</i></span></h2>
    <?php if (empty($stats['entry_pages'])): ?>
      <p class="no-data"><?= $t['no_external'] ?></p>
    <?php else:
      $maxE = max($stats['entry_pages']); $i = 1; ?>
      <ul class="rank-list">
      <?php foreach ($stats['entry_pages'] as $p => $c): ?>
        <li>
          <span class="rank-n"><?= $i++ ?></span>
          <span class="rank-label" title="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></span>
          <div class="rank-track"><div class="rank-fill" style="width:<?= round($c/$maxE*100) ?>%"></div></div>
          <span class="rank-count"><?= $c ?></span>
        </li>
      <?php endforeach; ?>
      </ul>
      <p class="section-note"><?= $t['entry_note'] ?></p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2><span class="card-title"><?= $t['browser_lang'] ?> <i class="info-btn" data-tip="<?= htmlspecialchars($t['tip_lang']) ?>">i</i></span></h2>
    <?php if (empty($stats['languages'])): ?>
      <p class="no-data"><?= $t['no_pages'] ?></p>
    <?php else:
      $maxL = max($stats['languages']); $i = 1; ?>
      <ul class="rank-list">
      <?php foreach ($stats['languages'] as $l => $c): ?>
        <li>
          <span class="rank-n"><?= $i++ ?></span>
          <span class="rank-label"><?= htmlspecialchars(langName($l, $langNames)) ?></span>
          <div class="rank-track"><div class="rank-fill" style="width:<?= round($c/$maxL*100) ?>%"></div></div>
          <span class="rank-count"><?= $c ?></span>
        </li>
      <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<div class="grid-3">
  <div class="card">
    <h2><span class="card-title"><?= $t['time_of_day'] ?> <i class="info-btn" data-tip="<?= htmlspecialchars($t['tip_tod']) ?>">i</i></span></h2>
    <div class="hours-chart">
      <?php for ($h = 0; $h < 24; $h++):
        $hh = $hourMax > 0 ? max(2, round($stats['hours'][$h]['views'] / $hourMax * 100)) : 2; ?>
        <div class="hour-col" title="<?= sprintf('%02d', $h) ?>:00 — <?= $stats['hours'][$h]['views'] ?> views">
          <div class="bar" style="height:<?= $hh ?>%"></div>
          <div class="lbl"><?= $h % 6 === 0 ? sprintf('%02d', $h) : '' ?></div>
        </div>
      <?php endfor; ?>
    </div>
    <?php
      $peakHour = array_search(max(array_column($stats['hours'], 'views')), array_column($stats['hours'], 'views'));
      $peakViews = $stats['hours'][$peakHour]['views'];
    ?>
    <?php if ($peakViews > 0): ?>
      <p class="section-note" style="margin-top:.6rem;">Peak: <?= sprintf('%02d', $peakHour) ?>:00 – <?= sprintf('%02d', ($peakHour + 1) % 24) ?>:00 · <?= $peakViews ?> <?= $t['views'] ?></p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2><span class="card-title"><?= $t['device_type'] ?> <i class="info-btn" data-tip="<?= htmlspecialchars($t['tip_device']) ?>">i</i></span></h2>
    <?php
      $deviceIcons = [
        'desktop' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
        'mobile'  => '<svg width="12" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><circle cx="12" cy="17" r="1" fill="currentColor"/></svg>',
        'tablet'  => '<svg width="12" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><circle cx="12" cy="17" r="1" fill="currentColor"/></svg>',
      ];
    ?>
    <?php foreach (['desktop','mobile','tablet'] as $type):
      $pct = $devTotal > 0 ? round($stats['devices'][$type] / $devTotal * 100) : 0; ?>
      <div class="dev-row">
        <span class="dev-label" style="display:flex;align-items:center;gap:.4rem;"><?= $deviceIcons[$type] ?><?= ucfirst($type) ?></span>
        <div class="dev-track"><div class="dev-fill <?= $type ?>" style="width:<?= $pct ?>%"></div></div>
        <span class="dev-pct"><?= $pct ?>%</span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <h2><span class="card-title"><?= $t['top_countries'] ?> <i class="info-btn" data-tip="<?= htmlspecialchars($t['tip_countries']) ?>">i</i></span></h2>
    <?php if (empty($stats['countries'])): ?>
      <p class="no-data"><?= $t['no_geo'] ?></p>
    <?php else:
      $maxC = max($stats['countries']); $i = 1; ?>
      <ul class="rank-list">
      <?php foreach (array_slice($stats['countries'], 0, 8, true) as $co => $c): ?>
        <li>
          <span class="rank-n"><?= $i++ ?></span>
          <span class="rank-label"><?= htmlspecialchars(countryName($co, $countryNames)) ?></span>
          <div class="rank-track"><div class="rank-fill" style="width:<?= round($c/$maxC*100) ?>%"></div></div>
          <span class="rank-count"><?= $c ?></span>
        </li>
      <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:.75rem;border-bottom:1px solid var(--border);margin-bottom:1rem;">
    <h2 style="border:none;padding:0;margin:0;"><?= sprintf($t['recent_hits'], RECENT_ENTRIES) ?></h2>
    <button onclick="var t=document.getElementById('recent-tbl');var b=this;t.style.display=t.style.display==='none'?'block':'none';b.textContent=t.style.display==='none'?'<?= addslashes($t['show']) ?>':'<?= addslashes($t['hide']) ?>';" style="background:none;border:1px solid var(--border);border-radius:8px;padding:.3rem .75rem;font-size:.78rem;color:var(--muted);cursor:pointer;"><?= $t['show'] ?></button>
  </div>
  <div id="recent-tbl" style="display:none;">
    <div class="tbl-wrap">
      <table>
        <thead>
          <tr><th>Date</th><th>Time</th><th>Page</th><th>Referrer</th><th>Device</th><th>Country</th></tr>
        </thead>
        <tbody>
          <?php foreach ($stats['recent'] as $r): ?>
          <tr>
            <td class="muted"><?= htmlspecialchars($r['date']) ?></td>
            <td class="muted"><?= htmlspecialchars($r['time']) ?></td>
            <td><?= htmlspecialchars($r['page']) ?></td>
            <td class="muted"><?= !empty($r['referrer']) ? htmlspecialchars(preg_replace('/^www\./', '', parse_url($r['referrer'], PHP_URL_HOST) ?: $r['referrer'])) : '—' ?></td>
            <td><span class="badge badge-<?= htmlspecialchars($r['device'] ?: 'desktop') ?>"><?= htmlspecialchars($r['device'] ?: 'desktop') ?></span></td>
            <td><?= htmlspecialchars($r['country'] ? countryName($r['country'], $countryNames) : '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php if ($advancedMode): ?><p class="file-info">analytics.db · <?= $stats['db_size'] ?> KB · <?= number_format($stats['db_rows']) ?> rows</p><?php endif; ?>
</div>

<?php endif; // total > 0 ?>

<?php if ($advancedMode): ?>
<div class="danger-zone">
  <h2><?= $t['danger_title'] ?></h2>
  <p><?= $t['danger_desc'] ?></p>
  <div class="db-info">analytics.db · <?= $stats['db_size'] ?> KB · <?= number_format($stats['db_rows']) ?> rows</div>
  <button class="btn-danger" onclick="document.getElementById('confirm-clear').classList.toggle('visible')"><?= $t['clear_btn'] ?></button>
  <div class="confirm-box" id="confirm-clear">
    <p><?= sprintf($t['confirm_msg'], number_format($stats['db_rows'])) ?></p>
    <form method="POST">
      <input type="hidden" name="clear_data" value="1">
      <input type="hidden" name="confirm_clear" value="yes">
      <button type="submit" class="btn-danger"><?= $t['confirm_btn'] ?></button>
    </form>
  </div>
</div>
<?php endif; ?>

</main>

<footer style="text-align:center;padding:1.5rem 1rem;font-size:.75rem;color:var(--muted);border-top:1px solid var(--border);margin-top:1rem;">
  <?= $t['powered_by'] ?> <a href="https://pimabox.com" style="color:var(--muted);text-decoration:underline;">pimabox</a> &mdash; <a href="https://github.com/ludescherstudio/pimabox" style="color:var(--muted);text-decoration:underline;">GitHub</a>
</footer>

<?php endif; // authed ?>
</body>
</html>
