<?php

/**
 * Plugin helper.
 */

defined('ABSPATH') || exit;

if (! function_exists("ApbdWps_SanitizeElitePostSlug")) {
    function ApbdWps_SanitizeElitePostSlug($name)
    {
        return sanitize_title_with_dashes('apbd-el-' . $name);
    }
}

if (! function_exists("ApbdWps_LoadPluginAPI")) {
    function ApbdWps_LoadPluginAPI($className = "", $sub_path = '', $defaultext = ".php")
    {
        if (! empty($className) && class_exists($className)) {
            return;
        }
        if (! ApbdWps_EndWith($className, $defaultext)) {
            $className .= ".php";
        }
        if (! empty($sub_path)) {
            $sub_path = '/' . $sub_path;
        }
        $apifile = dirname(__FILE__) . "/../api/" . $sub_path . "/" . $className;
        if (file_exists($apifile)) {
            require_once $apifile;
        }
    }
}

if (! function_exists("ApbdWps_GetMimeType")) {
    function ApbdWps_GetMimeType($file)
    {
        if (function_exists("mime_content_type")) {
            return mime_content_type($file);
        }
        if (class_exists("finfo")) {
            $finfo = new finfo(FILEINFO_MIME);
            return $finfo->file($file, FILEINFO_MIME_TYPE);
        }
        return '';
    }
}

if (!function_exists('ApbdWps_GetUserRoleName')) {
    /**
     * @param WP_User $userObject
     * @return string
     */
    function ApbdWps_GetUserRoleName($userObject)
    {
        global $wp_roles;
        if (! empty($userObject->roles[0])) {
            $user_role_slug = $userObject->roles[0];
            return translate_user_role($wp_roles->roles[$user_role_slug]['name']);
        }

        if (is_super_admin($userObject->ID)) {
            return translate_user_role($wp_roles->roles['administrator']['name']);
        }

        return "";
    }
}

if (!function_exists('ApbdWps_EditorTextFilter')) {
    function ApbdWps_EditorTextFilter($string)
    {
        return ApbdWps_KsesHtml($string);
    }
}

if (!function_exists('ApbdWps_GetUserTitleById')) {
    function ApbdWps_GetUserTitleById($id)
    {
        if (empty($id)) {
            return '';
        }
        $user = get_user_by("id", $id);
        $title = $user->first_name . ' ' . $user->last_name; //Name of ticket user
        if (empty(trim($title))) {
            $title = $user->display_name;
        }
        return $title;
    }
}

if (!function_exists('ApbdWps_GetUserTitleByUser')) {
    /**
     * @param WP_User $user
     * @return string
     */
    function ApbdWps_GetUserTitleByUser($user)
    {
        $title = "";
        if ($user instanceof WP_User) {
            if (! empty($user->first_name) && property_exists($user, 'last_name')) {
                $title = $user->first_name . ' ' . $user->last_name; //Name of ticket user
                if (empty(trim($title))) {
                    $title = $user->display_name;
                }
            } elseif (! empty($user->display_name)) {
                $title = $user->display_name;
            }
        }
        return $title;
    }
}

if (!function_exists('ApbdWps_GetRoleUsers')) {
    /**
     * @param $role
     * @param $orderby
     * @param $order
     * @return WP_User []
     */
    function ApbdWps_GetRoleUsers($role, $orderby, $order)
    {
        $args = array(
            'role' => $role,
            'orderby' => $orderby,
            'order' => $order
        );
        $users = get_users($args);
        return $users;
    }
}

if (!function_exists('ApbdWps_GetFilesInDirectory')) {
    function ApbdWps_GetFilesInDirectory($dir_path, $extension = '')
    {
        $output = [];

        if (!is_dir($dir_path)) {
            return $output;
        }

        $files = scandir($dir_path);

        if (false === $files) {
            return $output;
        }

        foreach ($files as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }

            $file_path = $dir_path . DIRECTORY_SEPARATOR . $file;

            if (!is_file($file_path)) {
                continue;
            }

            if (!empty($extension)) {
                if ($extension === pathinfo($file, PATHINFO_EXTENSION)) {
                    $output[] = $file;
                }
            } else {
                $output[] = $file;
            }
        }

        return $output;
    }
}

if (!function_exists('ApbdWps_GetFilesBasename')) {
    function ApbdWps_GetFilesBasename($file = '')
    {
        $file = untrailingslashit($file);
        $file = str_replace('\\', '/', $file);
        $file_parts = explode('/', $file);

        if (is_array($file_parts) && !empty($file_parts)) {
            $file = end($file_parts);
        } else {
            $file = basename($file);
        }

        return $file;
    }
}
