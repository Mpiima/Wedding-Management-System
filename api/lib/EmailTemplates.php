<?php
/**
 * Email templates for automated notifications. Each function returns ['subject' => string, 'body' => string] (HTML).
 */
if (!function_exists('wmis_email_pledge_made')) {
    function wmis_email_pledge_made($memberName, $amountPledged, $pledgeDate = null) {
        $date = $pledgeDate ? date('F j, Y', strtotime($pledgeDate)) : date('F j, Y');
        $subject = 'Thank you for your pledge';
        $body = '<p>Dear ' . htmlspecialchars($memberName) . ',</p>';
        $body .= '<p>Thank you for your pledge of <strong>' . htmlspecialchars(number_format((float) $amountPledged, 2)) . '</strong>.</p>';
        $body .= '<p>We have recorded it on ' . htmlspecialchars($date) . '. We appreciate your support.</p>';
        $body .= '<p>Best regards,<br>WMIS</p>';
        return ['subject' => $subject, 'body' => $body];
    }
}
if (!function_exists('wmis_email_payment_made')) {
    function wmis_email_payment_made($memberName, $amount, $paidAt, $pledgeTotalPaid, $amountPledged) {
        $date = date('F j, Y', strtotime($paidAt));
        $subject = 'Payment received – pledge';
        $body = '<p>Dear ' . htmlspecialchars($memberName) . ',</p>';
        $body .= '<p>We have received your payment of <strong>' . htmlspecialchars(number_format((float) $amount, 2)) . '</strong> on ' . htmlspecialchars($date) . '.</p>';
        $body .= '<p>Total paid so far: ' . htmlspecialchars(number_format((float) $pledgeTotalPaid, 2)) . ' of ' . htmlspecialchars(number_format((float) $amountPledged, 2)) . ' pledged.</p>';
        $body .= '<p>Thank you.</p><p>Best regards,<br>WMIS</p>';
        return ['subject' => $subject, 'body' => $body];
    }
}
if (!function_exists('wmis_email_contribution_made')) {
    function wmis_email_contribution_made($memberName, $amount, $contributionDate = null) {
        $date = $contributionDate ? date('F j, Y', strtotime($contributionDate)) : date('F j, Y');
        $subject = 'Thank you for your contribution';
        $body = '<p>Dear ' . htmlspecialchars($memberName) . ',</p>';
        $body .= '<p>We have recorded your contribution of <strong>' . htmlspecialchars(number_format((float) $amount, 2)) . '</strong> on ' . htmlspecialchars($date) . '.</p>';
        $body .= '<p>We appreciate your generosity.</p><p>Best regards,<br>WMIS</p>';
        return ['subject' => $subject, 'body' => $body];
    }
}
if (!function_exists('wmis_email_expenditure_made')) {
    function wmis_email_expenditure_made($recipientName, $description, $amount, $expenditureDate = null) {
        $date = $expenditureDate ? date('F j, Y', strtotime($expenditureDate)) : date('F j, Y');
        $subject = 'New expenditure recorded';
        $body = '<p>Dear ' . htmlspecialchars($recipientName) . ',</p>';
        $body .= '<p>An expenditure has been recorded:</p>';
        $body .= '<ul><li><strong>Description:</strong> ' . htmlspecialchars($description) . '</li>';
        $body .= '<li><strong>Amount:</strong> ' . htmlspecialchars(number_format((float) $amount, 2)) . '</li>';
        $body .= '<li><strong>Date:</strong> ' . htmlspecialchars($date) . '</li></ul>';
        $body .= '<p>Best regards,<br>WMIS</p>';
        return ['subject' => $subject, 'body' => $body];
    }
}
if (!function_exists('wmis_email_meeting_created')) {
    function wmis_email_meeting_created($recipientName, $title, $meetingDate = null, $location = '') {
        $date = $meetingDate ? date('F j, Y', strtotime($meetingDate)) : 'TBD';
        $subject = 'Meeting scheduled: ' . $title;
        $body = '<p>Dear ' . htmlspecialchars($recipientName) . ',</p>';
        $body .= '<p>A meeting has been scheduled:</p>';
        $body .= '<ul><li><strong>Title:</strong> ' . htmlspecialchars($title) . '</li>';
        $body .= '<li><strong>Date:</strong> ' . htmlspecialchars($date) . '</li>';
        if ($location !== '') {
            $body .= '<li><strong>Location:</strong> ' . htmlspecialchars($location) . '</li>';
        }
        $body .= '</ul><p>Best regards,<br>WMIS</p>';
        return ['subject' => $subject, 'body' => $body];
    }
}
if (!function_exists('wmis_email_minutes_created')) {
    function wmis_email_minutes_created($recipientName, $meetingTitle, $minutesSummary = '') {
        $subject = 'Meeting minutes: ' . $meetingTitle;
        $body = '<p>Dear ' . htmlspecialchars($recipientName) . ',</p>';
        $body .= '<p>Meeting minutes have been published for: <strong>' . htmlspecialchars($meetingTitle) . '</strong>.</p>';
        if ($minutesSummary !== '') {
            $body .= '<p>' . nl2br(htmlspecialchars($minutesSummary)) . '</p>';
        }
        $body .= '<p>Best regards,<br>WMIS</p>';
        return ['subject' => $subject, 'body' => $body];
    }
}
if (!function_exists('wmis_email_contract_signed')) {
    function wmis_email_contract_signed($vendorName, $serviceName, $contractDetails = '') {
        $subject = 'Contract confirmation – ' . $serviceName;
        $body = '<p>Dear ' . htmlspecialchars($vendorName) . ',</p>';
        $body .= '<p>This is to confirm that a contract for <strong>' . htmlspecialchars($serviceName) . '</strong> has been recorded.</p>';
        if ($contractDetails !== '') {
            $body .= '<p>' . nl2br(htmlspecialchars($contractDetails)) . '</p>';
        }
        $body .= '<p>Best regards,<br>WMIS</p>';
        return ['subject' => $subject, 'body' => $body];
    }
}
if (!function_exists('wmis_email_pledge_reminder')) {
    function wmis_email_pledge_reminder($memberName, $amountPledged, $amountPaid, $pledgeDate = null) {
        $date = $pledgeDate ? date('F j, Y', strtotime($pledgeDate)) : '';
        $outstanding = max(0, (float) $amountPledged - (float) $amountPaid);
        $subject = 'Friendly reminder: your pledge';
        $body = '<p>Dear ' . htmlspecialchars($memberName) . ',</p>';
        $body .= '<p>This is a friendly reminder of your pledge of <strong>' . htmlspecialchars(number_format((float) $amountPledged, 2)) . '</strong>';
        if ($date) $body .= ' (pledged on ' . htmlspecialchars($date) . ')';
        $body .= '.</p>';
        $body .= '<p>Amount paid to date: ' . htmlspecialchars(number_format((float) $amountPaid, 2)) . '.';
        if ($outstanding > 0) {
            $body .= ' Outstanding balance: ' . htmlspecialchars(number_format($outstanding, 2)) . '.</p>';
        } else {
            $body .= '</p>';
        }
        $body .= '<p>Thank you for your support.</p><p>Best regards,<br>WMIS</p>';
        return ['subject' => $subject, 'body' => $body];
    }
}
