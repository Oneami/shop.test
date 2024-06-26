<?php

namespace App\Services\Payment;

use App\Enums\Payment\OnlinePaymentMethodEnum;
use App\Enums\Payment\OnlinePaymentStatusEnum;
use App\Models\Orders\Order;
use App\Services\Imap\ImapParseEmailService;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BelpostCODService
{
    /**
     * Imports an Excel file to process COD payments.
     *
     * @param  UploadedFile  $file  The uploaded Excel file.
     * @return array The result of the import process, including the count and sum of payments.
     */
    public function importExcelCOD(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $currentRow = 1;
        $parsedData = [];
        $result = [
            'count' => 0,
            'sum' => 0,
        ];

        while (($currentRow <= $sheet->getHighestRow())) {
            $trackCell = trim($sheet->getCell('F' . $currentRow)->getValue());
            preg_match('/№(.*?),/', $trackCell, $currentTrack);
            $currentTrack = $currentTrack[1] ?? null;
            if ($currentTrack) {
                $parsedData[$currentTrack] = trim($sheet->getCell('C' . $currentRow)->getValue());
            }
            $currentRow++;
        }

        $orders = Order::with([
            'onlinePayments',
            'track',
        ])->whereHas('track', fn ($query) => $query->whereIn('track_number', array_keys($parsedData)))
            ->get();
        foreach ($orders as $order) {
            $orderTrackNumber = $order->track->track_number;
            $paymentSum = (float)($parsedData[$orderTrackNumber] ?? 0);
            if ($paymentSum && !count($order->onlinePayments->where('amount', $paymentSum))) {
                $payment = $order->onlinePayments()->create([
                    'currency_code' => 'BYN',
                    'currency_value' => 1,
                    'amount' => $paymentSum,
                    'paid_amount' => $paymentSum,
                    'method_enum_id' => OnlinePaymentMethodEnum::COD,
                    'last_status_enum_id' => OnlinePaymentStatusEnum::SUCCEEDED,
                ]);
                $payment->statuses()->create([
                    'payment_status_enum_id' => OnlinePaymentStatusEnum::SUCCEEDED,
                ]);
                $result['count']++;
                $result['sum'] += $paymentSum;
            }
        }

        return $result;
    }

    /**
     * Parses the email and imports the COD Excel file into the system.
     */
    public function parseEmail(): bool
    {
        $imapConfigHost = config('services.imap.belpost_host');
        $imapConfigUser = config('services.imap.belpost_user');
        $imapConfigPass = config('services.imap.belpost_password');
        if ($imapConfigHost && $imapConfigUser && $imapConfigPass) {
            $parseEmailService = new ImapParseEmailService($imapConfigHost, $imapConfigUser, $imapConfigPass);
            $periods = new DatePeriod(
                new DateTime(date('Y-m-d', strtotime('-2 day'))),
                new DateInterval('P1D'),
                new DateTime(date('Y-m-d', strtotime('+1 day')))
            );
            foreach ($periods as $period) {
                $mails = $parseEmailService->getMessagesByDate('barocco.by', $period->format('Y-m-d'));
                if (!empty($mails)) {
                    foreach ($mails as $mail) {
                        $subject = $mail->getSubject();
                        if (str_contains($subject, 'Приложение к ППИ от Брестского филиала РУП')) {
                            $attachments = $mail->getAttachments();
                            foreach ($attachments as $attachment) {
                                $path = storage_path('app/public/belpost/cod/' . date('d-m-Y') . '/') . $attachment->getFilename();
                                File::ensureDirectoryExists(dirname($path));
                                File::put($path, $attachment->getDecodedContent());
                                $this->importExcelCOD(new UploadedFile($path, $attachment->getFilename()));
                            }
                        }
                    }
                }
            }

            return true;
        }

        return false;
    }
}
