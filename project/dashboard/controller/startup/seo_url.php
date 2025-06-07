<?php
class ControllerStartupSeoUrl extends Controller {
    public function index() {
        // إضافة قاعدة إعادة الكتابة لكائن URL
        $this->url->addRewrite($this);
    }

    public function rewrite($link) {
        if (strpos($link, 'index.php?route=') !== false) {
            $link = str_replace('index.php?route=', '', $link);
        }
        return $link;
    }
}
