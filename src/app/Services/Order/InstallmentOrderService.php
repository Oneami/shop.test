<?php

namespace App\Services\Order;

use App\Helpers\TextHelper;
use App\Models\Orders\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class InstallmentOrderService
{
    /**
     * Create installment form
     *
     * @return string
     */
    public function createInstallmentForm(Order $order)
    {
        $resultPath = '/storage/order_installments/' . $order->id . '.xlsx';
        File::ensureDirectoryExists(dirname(public_path($resultPath)));
        $order->loadMissing([
            'admin',
            'user.passport',
            'onlinePayments',
            'items' => fn ($query) => $query
                ->whereIn('status_key', Order::$itemDepartureStatuses)
                ->with('installment'),
            'user' => fn ($query) => $query->with('lastAddress'),
        ]);
        $spreadsheet = IOFactory::load(public_path('templates/installment_template.xlsx'));
        $firstName = ($order->first_name ?? $order->user->first_name ?? null);
        $lastName = ($order->last_name ?? $order->user->last_name ?? null);
        $patronymicName = ($order->patronymic_name ?? $order->user->patronymic_name ?? null);
        $firstSheet = clone $spreadsheet->getActiveSheet();

        $onlinePaymentsSum = $order->getAmountPaidOrders();
        $uniqItemsCount = $order->getUniqItemsCount();
        foreach ($order->items as $itemKey => $item) {
            if ($itemKey > 0) {
                $spreadsheet->addSheet($firstSheet);
                $spreadsheet->setActiveSheetIndex($itemKey);
            }
            $sheet = $spreadsheet->getActiveSheet()->setTitle('№' . $itemKey + 1);

            $sheet->setCellValue('AD1', $item->installment->contract_number ?? null);

            $itemPrice = $item->current_price;
            $itemPrice += $order->delivery_price ? ($order->delivery_price / $uniqItemsCount) : 0;
            $itemPrice -= $onlinePaymentsSum ? ($onlinePaymentsSum / $uniqItemsCount) : 0;
            $adminFio = $order?->admin?->user_last_name . ' ' . mb_strtoupper(mb_substr($order?->admin?->name, 0, 1)) . '.' . mb_strtoupper(mb_substr($order?->admin?->user_patronymic_name, 0, 1)) . '.';
            $adminTrustDate = isset($order->admin->trust_date) ? date('d.m.Y', strtotime($order?->admin?->trust_date)) : null;
            $adminTrustNumber = $order->admin->trust_number ?? null;
            $dateContractInstallment = Carbon::parse(($order->date_contract_installment ?? 'now'))->translatedFormat('d.m.Y');
            $sheet->unmergeCells('AL3:AW3');
            $sheet->mergeCells('AI3:AX3');
            $sheet->setCellValue('AI3', $dateContractInstallment);
            $sheet->setCellValue('E5', 'Общество с ограниченной ответственностью "БароккоСтайл", в лице специалиста по продажам');
            $sheet->setCellValue('B6', $adminFio . ", действующий на основании Доверенности №$adminTrustNumber от $adminTrustDate, именуемый в дальнейшем");
            $sheet->setCellValue('B7', "Продавец, с одной стороны, и $lastName $firstName $patronymicName, именуемая в дальнейшем");
            $sheet->setCellValue('B8', 'Покупатель, с другой стороны, заключили настоящий договор о нижеследующем:');

            $sheet->setCellValue('C11', ($item->product->brand->name ?? null) . ', ' . mb_strtolower($item->product->category->name ?? ''));
            $sheet->setCellValue('AD11', $item->product->sku ?? $item->product->title ?? null);
            $sheet->setCellValue('G12', $item->size->name ?? null);
            $sheet->setCellValue('H13', TextHelper::numberToMoneyShortString($itemPrice));
            $sheet->setCellValue('V13', TextHelper::numberToMoneyString($itemPrice));

            $sheet->setCellValue('J17', $dateContractInstallment);
            $sheet->setCellValue('J18', Carbon::parse($dateContractInstallment)->addMonth()->translatedFormat('d.m.Y'));
            $sheet->setCellValue('J19', Carbon::parse($dateContractInstallment)->addMonth()->translatedFormat('d.m.Y'));
            $sheet->setCellValue('J20', Carbon::parse($dateContractInstallment)->addMonths(2)->translatedFormat('d.m.Y'));

            $sheet->setCellValue('X16', ($itemPrice - (($item->installment->monthly_fee ?? 0) * 2)));
            $sheet->setCellValue('X17', $item->installment->monthly_fee ?? null);
            $sheet->setCellValue('X19', $item->installment->monthly_fee ?? null);

            $sheet->setCellValue('AD16', $dateContractInstallment);
            $sheet->setCellValue('AD17', Carbon::parse($dateContractInstallment)->addMonth()->translatedFormat('d.m.Y'));
            $sheet->setCellValue('AD19', Carbon::parse($dateContractInstallment)->addMonths(2)->translatedFormat('d.m.Y'));

            $sheet->setCellValue('Z33', $lastName);
            $sheet->setCellValue('Z34', $firstName);
            $sheet->setCellValue('Z35', 'Паспорт');
            $sheet->setCellValue('AL34', $patronymicName);
            $sheet->setCellValue('AM35', $order->user->passport->series . $order->user->passport->passport_number);
            $sheet->setCellValue('Z37', $order->user->passport->personal_number);
            $sheet->setCellValue('Z39', $order->user->passport->issued_by);
            $sheet->setCellValue('AH41', Carbon::parse($order->user->passport->issued_date)->translatedFormat('d.m.Y'));

            $sheet->setCellValue('Z43', $order->user->passport->registration_address ?? null);
            $sheet->setCellValue('AH47', substr(trim($order->phone), -9, -7));
            $sheet->setCellValue('AL47', substr(trim($order->phone), -7));
            $sheet->setCellValue('AK52', mb_strtoupper(mb_substr($firstName, 0, 1)) . '.' . mb_strtoupper(mb_substr($patronymicName, 0, 1)) . '. ' . $lastName);
            $sheet->setCellValue('L52', mb_strtoupper(mb_substr($order?->admin?->name, 0, 1)) . '.' . mb_strtoupper(mb_substr($order?->admin?->user_patronymic_name, 0, 1)) . '. ' . $order?->admin?->user_last_name);

            $item->installment->installment_form_file = $resultPath;
            $item->installment->save();
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save(public_path($resultPath));

        return url($resultPath);
    }
}
