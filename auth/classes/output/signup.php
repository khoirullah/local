<?php
namespace local_auth\output;

use renderable;
use templatable;
use renderer_base;

class signup implements renderable, templatable {
    private $data;
    private $errors;

    public function __construct($data = null, $errors = []) {
        $this->data = $data ?? (object)[];
        $this->errors = $errors;
    }

    public function export_for_template(renderer_base $output): array {
        global $CFG;

        return [
            'signupaction' => new \moodle_url('/local/auth/signup.php'),
            'sesskey' => sesskey(),
            'imageurl' => $CFG->wwwroot . '/theme/image.php/boost/theme/169/signup-img',
            'data' => $this->data,
            'errors' => $this->errors
        ];
    }
}
