<?php
class ControllerPosTerminal extends Controller {
    public function getTerminalsByBranch() {
        $json = array();

        if (isset($this->request->get['branch_id'])) {
            $branch_id = $this->request->get['branch_id'];
            $this->load->model('pos/terminal');
            $terminals = $this->model_pos_terminal->getTerminalsByBranch($branch_id);
            
            $json = $terminals;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}