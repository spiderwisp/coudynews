<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_main_classifieds_admin_page() {
    return new AWPCP_MainClassifiedsAdminPage();
}

class AWPCP_MainClassifiedsAdminPage {

    public function dispatch() {
        global $awpcp_db_version;
        global $message;
        global $hasextrafieldsmodule;
        global $extrafieldsversioncompatibility;

        $params = array(
            'awpcp_db_version' => $awpcp_db_version,
            'message' => $message,
            'hasextrafieldsmodule' => $hasextrafieldsmodule,
            'extrafieldsversioncompatibility' => $extrafieldsversioncompatibility,
        );

        $template = AWPCP_DIR . '/templates/admin/main-classifieds-admin-page.tpl.php';

        return awpcp_render_template( $template, $params );
    }
}
