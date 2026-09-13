<?php

return [
    'appointment' => [
        'booked' => 'Booked',
        'confirmed' => 'Confirmed',
        'completed' => 'Visited',
        'cancelled' => 'Cancelled',
        'no_show' => 'No-show',
    ],

    'facility_booking' => [
        'booked' => 'Booked',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'medicine_order' => [
        'placed' => 'Placed',
        'accepted' => 'Accepted by pharmacy',
        'out_for_delivery' => 'Out for delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ],

    'operation_request' => [
        'requested' => 'Awaiting hospital response',
        'offered' => 'Offer received',
        'accepted' => 'Confirmed',
        'declined' => 'Declined',
        'cancelled' => 'Cancelled',
        'completed' => 'Completed',
    ],

    'record_access_grant' => [
        'requested' => 'Requested',
        'otp_sent' => 'Awaiting your approval',
        'approved' => 'Approved',
        'denied' => 'Denied',
        'expired' => 'Expired',
    ],

    'medical_record_type' => [
        'prescription' => 'Prescription',
        'lab_result' => 'Lab Result',
        'diagnosis_note' => 'Diagnosis Note',
        'uploaded_document' => 'Uploaded Document',
        'facility_report' => 'Facility Report',
    ],

    'payment' => [
        'initiated' => 'Initiated',
        'pending' => 'Pending',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ],
];
