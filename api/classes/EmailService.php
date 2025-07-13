<?php
/**
 * Email Service Class
 * Handles email notifications for task assignments
 */

// Load Composer autoloader for PHPMailer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

class EmailService {
    private $fromEmail;
    private $fromName;
    private $smtpHost;
    private $smtpPort;
    private $smtpUsername;
    private $smtpPassword;
    private $useSMTP;
    
    public function __construct() {
        // Load email configuration
        $this->fromEmail = $_ENV['MAIL_FROM'] ?? 'noreply@taskmanager.com';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'Task Manager';
        $this->smtpHost = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $this->smtpPort = $_ENV['SMTP_PORT'] ?? 587;
        $this->smtpUsername = $_ENV['SMTP_USERNAME'] ?? '';
        $this->smtpPassword = $_ENV['SMTP_PASSWORD'] ?? '';
        $this->useSMTP = !empty($this->smtpUsername) && !empty($this->smtpPassword);
    }
    
    /**
     * Send task assignment notification
     */
    public function sendTaskAssignmentNotification($userEmail, $userName, $taskTitle, $taskDescription, $deadline, $assignedBy) {
        $subject = "New Task Assigned: " . $taskTitle;
        
        $message = $this->buildTaskAssignmentEmail($userName, $taskTitle, $taskDescription, $deadline, $assignedBy);
        
        return $this->sendEmail($userEmail, $subject, $message);
    }
    
    /**
     * Send task status update notification
     */
    public function sendTaskStatusUpdateNotification($userEmail, $userName, $taskTitle, $oldStatus, $newStatus) {
        $subject = "Task Status Updated: " . $taskTitle;
        
        $message = $this->buildTaskStatusUpdateEmail($userName, $taskTitle, $oldStatus, $newStatus);
        
        return $this->sendEmail($userEmail, $subject, $message);
    }
    
    /**
     * Send task deadline reminder
     */
    public function sendTaskDeadlineReminder($userEmail, $userName, $taskTitle, $deadline) {
        $subject = "Task Deadline Reminder: " . $taskTitle;
        
        $message = $this->buildTaskDeadlineReminderEmail($userName, $taskTitle, $deadline);
        
        return $this->sendEmail($userEmail, $subject, $message);
    }
    
    /**
     * Send overdue task notification
     */
    public function sendOverdueTaskNotification($userEmail, $userName, $taskTitle, $deadline) {
        $subject = "Overdue Task: " . $taskTitle;
        
        $message = $this->buildOverdueTaskEmail($userName, $taskTitle, $deadline);
        
        return $this->sendEmail($userEmail, $subject, $message);
    }
    
    /**
     * Build task assignment email template
     */
    private function buildTaskAssignmentEmail($userName, $taskTitle, $taskDescription, $deadline, $assignedBy) {
        $deadlineText = $deadline ? "Deadline: " . date('F j, Y', strtotime($deadline)) : "No deadline set";
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .task-details { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
                .footer { text-align: center; margin-top: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>New Task Assigned</h2>
                </div>
                <div class='content'>
                    <p>Hello {$userName},</p>
                    <p>You have been assigned a new task by {$assignedBy}:</p>
                    
                    <div class='task-details'>
                        <h3>{$taskTitle}</h3>
                        <p><strong>Description:</strong> {$taskDescription}</p>
                        <p><strong>{$deadlineText}</strong></p>
                        <p><strong>Status:</strong> Pending</p>
                    </div>
                    
                    <p>Please log in to your task management system to view and manage this task.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated email from Task Management System</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Build task status update email template
     */
    private function buildTaskStatusUpdateEmail($userName, $taskTitle, $oldStatus, $newStatus) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2196F3; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .status-change { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #2196F3; }
                .footer { text-align: center; margin-top: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Task Status Updated</h2>
                </div>
                <div class='content'>
                    <p>Hello {$userName},</p>
                    <p>The status of your task has been updated:</p>
                    
                    <div class='status-change'>
                        <h3>{$taskTitle}</h3>
                        <p><strong>Previous Status:</strong> {$oldStatus}</p>
                        <p><strong>New Status:</strong> {$newStatus}</p>
                    </div>
                    
                    <p>Please log in to your task management system to view the updated details.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated email from Task Management System</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Build task deadline reminder email template
     */
    private function buildTaskDeadlineReminderEmail($userName, $taskTitle, $deadline) {
        $daysLeft = ceil((strtotime($deadline) - time()) / (60 * 60 * 24));
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #FF9800; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .reminder { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #FF9800; }
                .footer { text-align: center; margin-top: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Task Deadline Reminder</h2>
                </div>
                <div class='content'>
                    <p>Hello {$userName},</p>
                    <p>This is a reminder that your task deadline is approaching:</p>
                    
                    <div class='reminder'>
                        <h3>{$taskTitle}</h3>
                        <p><strong>Deadline:</strong> " . date('F j, Y', strtotime($deadline)) . "</p>
                        <p><strong>Days Left:</strong> {$daysLeft} day(s)</p>
                    </div>
                    
                    <p>Please ensure you complete this task before the deadline.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated email from Task Management System</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Build overdue task email template
     */
    private function buildOverdueTaskEmail($userName, $taskTitle, $deadline) {
        $daysOverdue = ceil((time() - strtotime($deadline)) / (60 * 60 * 24));
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #F44336; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .overdue { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #F44336; }
                .footer { text-align: center; margin-top: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Overdue Task</h2>
                </div>
                <div class='content'>
                    <p>Hello {$userName},</p>
                    <p>Your task is now overdue:</p>
                    
                    <div class='overdue'>
                        <h3>{$taskTitle}</h3>
                        <p><strong>Deadline:</strong> " . date('F j, Y', strtotime($deadline)) . "</p>
                        <p><strong>Days Overdue:</strong> {$daysOverdue} day(s)</p>
                    </div>
                    
                    <p>Please complete this task as soon as possible.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated email from Task Management System</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Send email using SMTP or PHP mail function
     */
    private function sendEmail($to, $subject, $message) {
        if ($this->useSMTP) {
            return $this->sendEmailViaSMTP($to, $subject, $message);
        } else {
            return $this->sendEmailViaMail($to, $subject, $message);
        }
    }
    
    /**
     * Send email via SMTP
     */
    private function sendEmailViaSMTP($to, $subject, $message) {
        try {
            // Check if PHPMailer is available
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                return $this->sendEmailViaPHPMailer($to, $subject, $message);
            } else {
                // Fallback to basic SMTP with fsockopen
                return $this->sendEmailViaBasicSMTP($to, $subject, $message);
            }
        } catch (Exception $e) {
            error_log("SMTP Email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email via PHPMailer (if available)
     */
    private function sendEmailViaPHPMailer($to, $subject, $message) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtpPort;
            
            // Recipients
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer error: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Send email via basic SMTP (fallback)
     */
    private function sendEmailViaBasicSMTP($to, $subject, $message) {
        // This is a simplified SMTP implementation
        // In production, you should use PHPMailer or similar library
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }
    
    /**
     * Send email via PHP mail function
     */
    private function sendEmailViaMail($to, $subject, $message) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        try {
            $result = mail($to, $subject, $message, implode("\r\n", $headers));
            
            if (!$result) {
                error_log("Failed to send email to: $to");
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send bulk notifications
     */
    public function sendBulkNotifications($notifications) {
        $results = [];
        
        foreach ($notifications as $notification) {
            $result = $this->sendEmail(
                $notification['email'],
                $notification['subject'],
                $notification['message']
            );
            
            $results[] = [
                'email' => $notification['email'],
                'success' => $result
            ];
        }
        
        return $results;
    }
    
    /**
     * Test email configuration
     */
    public function testEmailConfiguration($testEmail) {
        $subject = "Test Email - Task Management System";
        $message = "
        <html>
        <body>
            <h2>Email Configuration Test</h2>
            <p>If you receive this email, your email configuration is working correctly.</p>
            <p>Test sent at: " . date('Y-m-d H:i:s') . "</p>
        </body>
        </html>
        ";
        
        return $this->sendEmail($testEmail, $subject, $message);
    }
}