<?php
/**
 * Flash Message System for Event Management System
 * Handles success, error, warning, and info messages
 */

class Flash {
    private $messages = [];

    public function __construct() {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        $this->messages = &$_SESSION['flash_messages'];
    }

    /**
     * Add a success message
     */
    public function success($message) {
        $this->add('success', $message);
    }

    /**
     * Add an error message
     */
    public function error($message) {
        $this->add('error', $message);
    }

    /**
     * Add a warning message
     */
    public function warning($message) {
        $this->add('warning', $message);
    }

    /**
     * Add an info message
     */
    public function info($message) {
        $this->add('info', $message);
    }

    /**
     * Add a message of any type
     */
    private function add($type, $message) {
        if (!isset($this->messages[$type])) {
            $this->messages[$type] = [];
        }
        $this->messages[$type][] = $message;
    }

    /**
     * Get all messages
     */
    public function getMessages() {
        $messages = $this->messages;
        $this->clear();
        return $messages;
    }

    /**
     * Get messages of a specific type
     */
    public function getMessagesByType($type) {
        $messages = $this->messages[$type] ?? [];
        $this->clearType($type);
        return $messages;
    }

    /**
     * Check if there are any messages
     */
    public function hasMessages() {
        return !empty($this->messages);
    }

    /**
     * Check if there are messages of a specific type
     */
    public function hasMessagesOfType($type) {
        return isset($this->messages[$type]) && !empty($this->messages[$type]);
    }

    /**
     * Clear all messages
     */
    public function clear() {
        $this->messages = [];
        $_SESSION['flash_messages'] = [];
    }

    /**
     * Clear messages of a specific type
     */
    public function clearType($type) {
        if (isset($this->messages[$type])) {
            unset($this->messages[$type]);
            unset($_SESSION['flash_messages'][$type]);
        }
    }

    /**
     * Render flash messages as HTML
     */
    public function render() {
        $messages = $this->getMessages();
        
        if (empty($messages)) {
            return '';
        }

        $html = '<div class="flash-messages">';
        
        foreach ($messages as $type => $typeMessages) {
            foreach ($typeMessages as $message) {
                $html .= $this->renderMessage($type, $message);
            }
        }
        
        $html .= '</div>';
        return $html;
    }

    /**
     * Render a single message as HTML
     */
    private function renderMessage($type, $message) {
        $alertClass = $this->getAlertClass($type);
        $icon = $this->getIcon($type);
        
        return "
        <div class=\"alert $alertClass alert-dismissible fade show\" role=\"alert\">
            <i class=\"$icon\"></i>
            " . htmlspecialchars($message) . "
            <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
        </div>
        ";
    }

    /**
     * Get Bootstrap alert class for message type
     */
    private function getAlertClass($type) {
        $classes = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ];
        return $classes[$type] ?? 'alert-secondary';
    }

    /**
     * Get Font Awesome icon for message type
     */
    private function getIcon($type) {
        $icons = [
            'success' => 'fas fa-check-circle',
            'error' => 'fas fa-exclamation-circle',
            'warning' => 'fas fa-exclamation-triangle',
            'info' => 'fas fa-info-circle'
        ];
        return $icons[$type] ?? 'fas fa-info';
    }
}