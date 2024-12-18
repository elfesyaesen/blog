<?php

namespace System\Engine;

class Controller
{
    use Response, Request;
    protected $data = null;
    public function __construct()
    {
        if (isset($_SESSION["csrf"])) {
            $this->data["csrf"] = $_SESSION["csrf"];
        } else {
            $this->data["csrf"] = $_SESSION["csrf"] = bin2hex(random_bytes(16));
        }
    }

    public function view(string $path, array $data = []): void
    {
        $view = APP_ROOT . '/view/' . $path . '.php';
        if (file_exists($view)) {
            extract($data);
            require_once($view);
        } else {
            require_once APP_ROOT . '/view/404.php';
        }
    }
}
