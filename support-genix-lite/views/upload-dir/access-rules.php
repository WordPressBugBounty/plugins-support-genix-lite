<?php

/**
 * Upload directory .htaccess rules, returned to Apbd_Wps_UploadDir::Ensure().
 */

defined('ABSPATH') || exit;

return <<<'UPLOAD_DIR_ACCESS_RULES'
# Support Genix - upload directory hardening (do not edit; managed by plugin)
# Deny ALL direct HTTP access to this directory. Every file here (ticket/reply
# attachments) is served only through the authenticated PHP proxy
# (admin-ajax action=..._file_dl), which reads from disk via WP_Filesystem and
# is therefore unaffected by these rules. Nothing in this tree is meant to be
# fetched directly, so a blanket deny is safe and total.
# NOTE: .htaccess is honoured by Apache/LiteSpeed but ignored by nginx; nginx
# users must add an equivalent "location" deny rule at the server level.
<IfModule authz_core_module>
    Require all denied
</IfModule>
<IfModule !authz_core_module>
    Order allow,deny
    Deny from all
</IfModule>

# Block access to hidden/system files
<FilesMatch "^\.">
    <IfModule authz_core_module>
        Require all denied
    </IfModule>
    <IfModule !authz_core_module>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>

# Deny direct access to executable / script files of any kind
<FilesMatch "(?i)\.(php|phtml|phar|phps|pht|php[3-8]|js|mjs|html|htm|shtml|sh|bash|zsh|csh|ksh|cgi|pl|py|rb|asp|aspx|jsp|jspx|svg|htaccess|htpasswd)$">
    <IfModule authz_core_module>
        Require all denied
    </IfModule>
    <IfModule !authz_core_module>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>

# Disable PHP handlers if mod_php / mod_mime is loaded
<IfModule mod_php5.c>
    php_flag engine off
</IfModule>
<IfModule mod_php7.c>
    php_flag engine off
</IfModule>
<IfModule mod_php8.c>
    php_flag engine off
</IfModule>
<IfModule mod_mime.c>
    RemoveHandler .php .phtml .phar .phps .pht .php3 .php4 .php5 .php6 .php7 .php8
    RemoveType .php .phtml .phar .phps .pht .php3 .php4 .php5 .php6 .php7 .php8
    AddType text/plain .php .phtml .phar .phps .pht .php3 .php4 .php5 .php6 .php7 .php8 .js .mjs .html .htm .shtml .sh .cgi .pl .py .svg
</IfModule>

# Prevent directory listing
Options -Indexes -ExecCGI

# Strip any X-Powered-By disclosure
<IfModule mod_headers.c>
    Header unset X-Powered-By
</IfModule>

UPLOAD_DIR_ACCESS_RULES;
