<?
class CPL_Admin_Modules_Hms_Reports_Controller extends CP_Common_Lib_ModuleControllerAbstract
{
    function getSearch(){
        return $this->view->getSearch();
    }

    function getDisplayReport(){
        $fn = Zend_Registry::get('fn');

        set_time_limit(50000);
        $report = $fn->getReqParam('report');
        $fnName = 'get' . ucfirst($report);
        $text = $this->$fnName();
        return $this->view->getDisplayReport($text);
    }

    function getExportData(){
        $fn = Zend_Registry::get('fn');

        set_time_limit(50000);
        $report = $fn->getReqParam('report');
        $fnName = 'get' . ucfirst($report) . 'Export';
        return $this->$fnName();
    }

    function getExportDataPdf(){
        $fn = Zend_Registry::get('fn');

        set_time_limit(50000);
        $report = $fn->getReqParam('report');
        $fnName = 'get' . ucfirst($report) . 'ExportAsPdf';
        return $this->$fnName();
    }
     function getReturnMedicineReport() {
        $wReturnMedicineReport = getCPWidgetObj('hms_returnMedicineReport');
        return $wReturnMedicineReport->getWidget();
    }

    function getReturnMedicineReportExport() {
        $wReturnMedicineReport = getCPWidgetObj('hms_returnMedicineReport');
        return $wReturnMedicineReport->model->getExportToExcel();
    }
    function getReferenceDoctorAppointmentReport() {
        $wReferenceDoctorAppointmentReport = getCPWidgetObj('hms_referenceDoctorAppointmentReport');
        return $wReferenceDoctorAppointmentReport->getWidget();
    }

    function getReferenceDoctorAppointmentReportExport() {
        $wReferenceDoctorAppointmentReport = getCPWidgetObj('hms_referenceDoctorAppointmentReport');
        return $wReferenceDoctorAppointmentReport->model->getExportToExcel();
    }
    
    function getPatientVisitSummary() {
        $wPatientVisitSummary = getCPWidgetObj('hms_patientVisitSummary');
        return $wPatientVisitSummary->getWidget();
    }

    function getPatientVisitSummaryExport() {
        $wPatientVisitSummary = getCPWidgetObj('hms_patientVisitSummary');
        return $wPatientVisitSummary->model->getExportToExcel();
    }

    function getDailyCollectionReport() {
        $wDailyCollectionReport = getCPWidgetObj('hms_dailyCollectionReport');
        return $wDailyCollectionReport->getWidget();
    }

    function getDailyCollectionReportExport() {
        $wDailyCollectionReport = getCPWidgetObj('hms_dailyCollectionReport');
        return $wDailyCollectionReport->model->getExportToExcel();
    }

    function getRevenueByDay() {
        $wRevenueByDay = getCPWidgetObj('hms_revenueByDay');
        return $wRevenueByDay->getWidget();
    }

    function getRevenueByDayExport() {
        $wRevenueByDay = getCPWidgetObj('hms_revenueByDay');
        return $wRevenueByDay->model->getExportToExcel();
    }

    function getRevenueByMonth() {
        $wRevenueByMonth = getCPWidgetObj('hms_revenueByMonth');
        return $wRevenueByMonth->getWidget();
    }

    function getRevenueByMonthExport() {
        $wRevenueByMonth = getCPWidgetObj('hms_revenueByMonth');
        return $wRevenueByMonth->model->getExportToExcel();
    }

    function getTreatmentHistory() {
        $wTreatmentHistory = getCPWidgetObj('hms_treatmentHistory');
        return $wTreatmentHistory->getWidget();
    }

    function getTreatmentHistoryExport() {
        $wTreatmentHistory = getCPWidgetObj('hms_treatmentHistory');
        return $wTreatmentHistory->model->getExportToExcel();
    }

    function getVisitByDay() {
        $wVisitByDay = getCPWidgetObj('hms_visitByDay');
        return $wVisitByDay->getWidget();
    }

    function getVisitByDayExport() {
        $wVisitByDay = getCPWidgetObj('hms_visitByDay');
        return $wVisitByDay->model->getExportToExcel();
    }

    function getInvoiceSummary() {
        $wInvoiceSummary = getCPWidgetObj('hms_invoiceSummary');
        return $wInvoiceSummary->getWidget();
    }

    function getInvoiceSummaryExport() {
        $wInvoiceSummary = getCPWidgetObj('hms_invoiceSummary');
        return $wInvoiceSummary->model->getExportToExcel();
    }

    function getInvoiceSummaryExportAsPdf() {
        $wInvoiceSummary = getCPWidgetObj('hms_invoiceSummary');
        return $wInvoiceSummary->model->getExportToPdf();
    }

    function getCompanyInvoiceSummary() {
        $wCompanyInvoiceSummary = getCPWidgetObj('hms_companyInvoiceSummary');
        return $wCompanyInvoiceSummary->getWidget();
    }

    function getCompanyInvoiceSummaryExport() {
        $wCompanyInvoiceSummary = getCPWidgetObj('hms_companyInvoiceSummary');
        return $wCompanyInvoiceSummary->model->getExportToExcel();
    }

    function getCompanyInvoiceSummaryExportAsPdf() {
        $wCompanyInvoiceSummary = getCPWidgetObj('hms_companyInvoiceSummary');
        return $wCompanyInvoiceSummary->model->getExportToPdf();
    }

    function getPanelInvoiceSummary() {
        $wPanelInvoiceSummary = getCPWidgetObj('hms_panelInvoiceSummary');
        return $wPanelInvoiceSummary->getWidget();
    }

    function getPanelInvoiceSummaryExport() {
        $wPanelInvoiceSummary = getCPWidgetObj('hms_panelInvoiceSummary');
        return $wPanelInvoiceSummary->model->getExportToExcel();
    }

    function getPanelInvoiceSummaryExportAsPdf() {
        $wPanelInvoiceSummary = getCPWidgetObj('hms_panelInvoiceSummary');
        return $wPanelInvoiceSummary->model->getExportToPdf();
    }

    function getExportToPdfMBPJ() {
        $wPanelInvoiceSummary = getCPWidgetObj('hms_panelInvoiceSummary');
        return $wPanelInvoiceSummary->model->getExportToPdfMBPJ();
    }

    function getExportToPdfSyabas() {
        $wPanelInvoiceSummary = getCPWidgetObj('hms_panelInvoiceSummary');
        return $wPanelInvoiceSummary->model->getExportToPdfSyabas();
    }

    function getExportToPdfSDN() {
        $wPanelInvoiceSummary = getCPWidgetObj('hms_panelInvoiceSummary');
        return $wPanelInvoiceSummary->model->getExportToPdfSDN();
    }

    function getExpenseReport() {
        $wExpenseReport = getCPWidgetObj('hms_expenseReport');
        return $wExpenseReport->getWidget();
    }

    function getExpenseReportExport() {
        $wExpenseReport = getCPWidgetObj('hms_expenseReport');
        return $wExpenseReport->model->getExportToExcel();
    }

    function getStockReport() {
        $wStockReport = getCPWidgetObj('hms_stockReport');
        return $wStockReport->getWidget();
    }

    function getStockReportExport() {
        $wStockReport = getCPWidgetObj('hms_stockReport');
        return $wStockReport->model->getExportToExcel();
    }


    function getDutyRosterReport() {
        $wDutyRosterReport = getCPWidgetObj('hms_dutyRosterReport');
        return $wDutyRosterReport->getWidget();
    }

    function getDutyRosterReportExport() {
        $wDutyRosterReport = getCPWidgetObj('hms_dutyRosterReport');
        return $wDutyRosterReport->model->getExportToExcel();
    }



    function getExportStocksToPdf() {
        $wStockReport = getCPWidgetObj('hms_stockReport');
        return $wStockReport->model->getExportStocksToPdf();
    }


    function getProfitByMonth() {
        $wProfitByMonth = getCPWidgetObj('tradingsg_profitByMonth');
        return $wProfitByMonth->getWidget();
    }

    function getProfitByMonthExport() {
        $wProfitByMonth = getCPWidgetObj('tradingsg_profitByMonth');
        return $wProfitByMonth->model->getExportToExcel();
    }

    function getProfitByYear() {
        $wProfitByYear = getCPWidgetObj('tradingsg_profitByYear');
        return $wProfitByYear->getWidget();
    }

    function getProfitByYearExport() {
        $wProfitByYear = getCPWidgetObj('tradingsg_profitByYear');
        return $wProfitByYear->model->getExportToExcel();
    }

    function getQuoteByMonth() {
        $wQuoteByMonth = getCPWidgetObj('tradingsg_quoteByMonth');
        return $wQuoteByMonth->getWidget();
    }

    function getQuoteByMonthExport() {
        $wQuoteByMonth = getCPWidgetObj('tradingsg_quoteByMonth');
        return $wQuoteByMonth->model->getExportToExcel();
    }

    function getQuoteByYear() {
        $wQuoteByYear = getCPWidgetObj('tradingsg_quoteByYear');
        return $wQuoteByYear->getWidget();
    }

    function getSalesByClient() {
        $wSalesByClient = getCPWidgetObj('tradingsg_salesByClient');
        return $wSalesByClient->getWidget();
    }

    function getSalesByClientExport() {
        $wSalesByClient = getCPWidgetObj('tradingsg_salesByClient');
        return $wSalesByClient->model->getExportToExcel();
    }

    function getInvoiceByClient() {
        $wInvoiceByClient = getCPWidgetObj('tradingsg_invoiceByClient');
        return $wInvoiceByClient->getWidget();
    }

    function getInvoiceByClientExport() {
        $wInvoiceByClient = getCPWidgetObj('tradingsg_invoiceByClient');
        return $wInvoiceByClient->model->getExportToExcel();
    }

    function getEnquiryByMonth() {
        $wEnquiryByMonth = getCPWidgetObj('tradingsg_enquiryByMonth');
        return $wEnquiryByMonth->getWidget();
    }

    function getEnquiryByMonthExport() {
        $wEnquiryByMonth = getCPWidgetObj('tradingsg_enquiryByMonth');
        return $wEnquiryByMonth->model->getExportToExcel();
    }

    function getEnquiryByYear() {
        $wEnquiryByYear = getCPWidgetObj('tradingsg_enquiryByYear');
        return $wEnquiryByYear->getWidget();
    }

    function getLeadByStaff() {
        $wLeadByStaff = getCPWidgetObj('tradingsg_leadByStaff');
        return $wLeadByStaff->getWidget();
    }

    function getLeadByStaffExport() {
        $wLeadByStaff = getCPWidgetObj('tradingsg_leadByStaff');
        return $wLeadByStaff->model->getExportToExcel();
    }

    function getEnquiryByStaff() {
        $wEnquiryByStaff = getCPWidgetObj('tradingsg_enquiryByStaff');
        return $wEnquiryByStaff->getWidget();
    }

    function getEnquiryByStaffExport() {
        $wEnquiryByStaff = getCPWidgetObj('tradingsg_enquiryByStaff');
        return $wEnquiryByStaff->model->getExportToExcel();
    }

    function getEnquiryActivityByStaff() {
        $wEnquiryActivityByStaff = getCPWidgetObj('tradingsg_enquiryActivityByStaff');
        return $wEnquiryActivityByStaff->getWidget();
    }

    function getEnquiryActivityByStaffExport() {
        $wEnquiryActivityByStaff = getCPWidgetObj('tradingsg_enquiryActivityByStaff');
        return $wEnquiryActivityByStaff->model->getExportToExcel();
    }

    function getSalesSummaryByProduct() {
        $wSalesSummaryByProduct = getCPWidgetObj('tradingsg_salesSummaryByProduct');
        return $wSalesSummaryByProduct->getWidget();
    }

    function getSalesSummaryByProductExport() {
        $wSalesSummaryByProduct = getCPWidgetObj('tradingsg_salesSummaryByProduct');
        return $wSalesSummaryByProduct->model->getExportToExcel();
    }

    function getSalesSummaryByProductGroup() {
        $wSalesSummaryByProduct = getCPWidgetObj('tradingsg_salesSummaryByProductGroup');
        return $wSalesSummaryByProduct->getWidget();
    }

    function getSalesSummaryByProductGroupExport() {
        $wSalesSummaryByProduct = getCPWidgetObj('tradingsg_salesSummaryByProductGroup');
        return $wSalesSummaryByProduct->model->getExportToExcel();
    }

    function getInvoicesForVat() {
        $wInvoicesForVat = getCPWidgetObj('tradingsg_invoicesForVat');
        return $wInvoicesForVat->getWidget();
    }

    function getInvoicesForVatExport() {
        $wInvoicesForVat = getCPWidgetObj('tradingsg_invoicesForVat');
        return $wInvoicesForVat->model->getExportToExcel();
    }

    function getInvoicesByVatPercent() {
        $wInvoicesByVatPercent = getCPWidgetObj('tradingsg_invoicesByVatPercent');
        return $wInvoicesByVatPercent->getWidget();
    }

    function getInvoicesByVatPercentExport() {
        $wInvoicesByVatPercent = getCPWidgetObj('tradingsg_invoicesByVatPercent');
        return $wInvoicesByVatPercent->model->getExportToExcel();
    }

    function getDetailVatPercentForInvoice() {
        $wDetailVatPercentForInvoice = getCPWidgetObj('tradingsg_detailVatPercentForInvoice');
        return $wDetailVatPercentForInvoice->getWidget();
    }

    function getDetailVatPercentForInvoiceExport() {
        $wDetailVatPercentForInvoice = getCPWidgetObj('tradingsg_detailVatPercentForInvoice');
        return $wDetailVatPercentForInvoice->model->getExportToExcel();
    }

    function getDailyCollectionReport1() {
        $wDailyCollectionReport = getCPWidgetObj('tradingsg_dailyCollectionReport');
        return $wDailyCollectionReport->getWidget();
    }

    function getDailyCollectionReportExport1() {
        $wDailyCollectionReport = getCPWidgetObj('tradingsg_dailyCollectionReport');
        return $wDailyCollectionReport->model->getExportToExcel();
    }

    function getDetailCollectionReport() {
        $wDetailCollectionReport = getCPWidgetObj('tradingsg_detailCollectionReport');
        return $wDailyCollectionReport->getWidget();
    }

    function getDetailCollectionReportExport() {
        $wDetailCollectionReport = getCPWidgetObj('tradingsg_detailCollectionReport');
        return $wDetailCollectionReport->model->getExportToExcel();
    }

    function getQuoteByStaff() {
        $wQuoteByStaff = getCPWidgetObj('tradingsg_quoteByStaff');
        return $wQuoteByStaff->getWidget();
    }

    function getQuoteByStaffExport() {
        $wQuoteByStaff = getCPWidgetObj('tradingsg_quoteByStaff');
        return $wQuoteByStaff->model->getExportToExcel();
    }

    function getSummaryByClient() {
        $wSummaryByClient= getCPWidgetObj('tradingsg_summaryByClient');
        return $wSummaryByClient->getWidget();
    }

    function getSummaryByClientExport() {
        $wSummaryByClient = getCPWidgetObj('tradingsg_summaryByClient');
        return $wSummaryByClient->model->getExportToExcel();
    }

    function getDetailSummaryByClient() {
        $wDetailSummaryByClient= getCPWidgetObj('tradingsg_detailSummaryByClient');
        return $wDetailSummaryByClient->getWidget();
    }

    function getDetailSummaryByClientExport() {
        $wDetailSummaryByClient = getCPWidgetObj('tradingsg_detailSummaryByClient');
        return $wDetailSummaryByClient->model->getExportToExcel();
    }






    function getSummaryPurchaseSales() {
        $wSummaryPurchaseSalesReport= getCPWidgetObj('tradingsg_summaryPurchaseSalesReport');
        return $wSummaryPurchaseSalesReport->getWidget();
    }

    function getSummaryPurchaseSalesExport() {
        $wSummaryPurchaseSalesReport = getCPWidgetObj('tradingsg_summaryPurchaseSalesReport');
        return $wSummaryPurchaseSalesReport->model->getExportToExcel();
    }

    function getSummaryPurchase() {
        $wSummaryPurchaseReport= getCPWidgetObj('tradingsg_summaryPurchaseReport');
        return $wSummaryPurchaseReport->getWidget();
    }

    function getSummaryPurchaseExport() {
        $wSummaryPurchaseReport = getCPWidgetObj('tradingsg_summaryPurchaseReport');
        return $wSummaryPurchaseReport->model->getExportToExcel();
    }

    function getSummarySales() {
        $wSummarySalesReport= getCPWidgetObj('tradingsg_summarySalesReport');
        return $wSummarySalesReport->getWidget();
    }

    function getSummarySalesExport() {
        $wSummarySalesReport = getCPWidgetObj('tradingsg_summarySalesReport');
        return $wSummarySalesReport->model->getExportToExcel();
    }

    function getOverallGstSummary() {
        $wOverallGstSummary = getCPWidgetObj('tradingsg_overallGstSummary');
        return $wOverallGstSummary->getWidget();
    }

    function getOverallGstSummaryExport() {
        $wOverallGstSummary = getCPWidgetObj('tradingsg_overallGstSummary');
        return $wOverallGstSummary->model->getExportToExcel();
    }

    function getOverallSalesSummary() {
        $wOverallSalesSummary = getCPWidgetObj('tradingsg_overallSalesSummary');
        return $wOverallSalesSummary->getWidget();
    }

    function getOverallSalesSummaryExport() {
        $wOverallSalesSummary = getCPWidgetObj('tradingsg_overallSalesSummary');
        return $wOverallSalesSummary->model->getExportToExcel();
    }

    function getCompanyPatientSqlByBillType() {
        return $this->model->getCompanyPatientSqlByBillType();
    }

    function getLabReportSummary() {
        $wLabReportSummary = getCPWidgetObj('hms_labReportSummary');
        return $wLabReportSummary->getWidget();
    }

    function getLabReportSummaryExport() {
        $wLabReportSummary = getCPWidgetObj('hms_labReportSummary');
        return $wLabReportSummary->model->getExportToExcel();
    }

    function getLabReport() {
        $wLabReport = getCPWidgetObj('hms_labReport');
        return $wLabReport->getWidget();
    }

    function getLabDetailReportExport() {
        $wLabDetailReport = getCPWidgetObj('hms_labDetailReport');
        return $wLabDetailReport->model->getExportToExcel();
    }

    function getLabDetailReport() {
        $wLabDetailReport = getCPWidgetObj('hms_labDetailReport');
        return $wLabDetailReport->getWidget();
    }

    function getLabReportExport() {
        $wLabReport = getCPWidgetObj('hms_labReport');
        return $wLabReport->model->getExportToExcel();
    }

    function getImageReport() {
        $wImageReport = getCPWidgetObj('hms_imageReport');
        return $wImageReport->getWidget();
    }

    function getImageReportExport() {
        $wImageReport = getCPWidgetObj('hms_imageReport');
        return $wImageReport->model->getExportToExcel();
    }

    function getDrPaymentReport() {
        $wDrPaymentReport = getCPWidgetObj('hms_drPaymentReport');
        return $wDrPaymentReport->getWidget();
    }

    function getDrPaymentReportExport() {
        $wDrPaymentReport = getCPWidgetObj('hms_drPaymentReport');
        return $wDrPaymentReport->model->getExportToExcel();
    }

    function getPatientVisitByMonth() {
        $wPatientVisitByMonth = getCPWidgetObj('hms_patientVisitByMonth');
        return $wPatientVisitByMonth->getWidget();
    }

    function getPatientVisitByMonthExport() {
        $wPatientVisitByMonth = getCPWidgetObj('hms_patientVisitByMonth');
        return $wPatientVisitByMonth->model->getExportToExcel();
    }

    function getInPatientReport() {
        $wInPatientReport = getCPWidgetObj('hms_inPatientReport');
        return $wInPatientReport->getWidget();
    }

    function getInPatientReportExport() {
        $wInPatientReport = getCPWidgetObj('hms_inPatientReport');
        return $wInPatientReport->model->getExportToExcel();
    }

    function getAttendanceReport() {
        $wAttendanceReport = getCPWidgetObj('hms_attendanceReport');
        return $wAttendanceReport->getWidget();
    }

    function getAttendanceReportExport() {
        $wAttendanceReport = getCPWidgetObj('hms_attendanceReport');
        return $wAttendanceReport->model->getExportToExcel();
    }

     function getOverallAnalysis() {
        $wOverallAnalysis = getCPWidgetObj('hms_overallAnalysis');
        return $wOverallAnalysis->getWidget();
    }

    function getOverallAnalysisExport() {
        $wOverallAnalysis = getCPWidgetObj('hms_overallAnalysis');
        return $wOverallAnalysis->model->getExportToExcel();
    }

    function getBalanceSheetReport() {
        $wBalanceSheetReport = getCPWidgetObj('hms_balanceSheetReport');
        return $wBalanceSheetReport->getWidget();
    }

    function getBalanceSheetReportExport() {
        $wBalanceSheetReport = getCPWidgetObj('hms_balanceSheetReport');
        return $wBalanceSheetReport->model->getExportToExcel();
    }

    function getBalanceSheetLabReport() {
        $wBalanceSheetLabReport = getCPWidgetObj('hms_balanceSheetLabReport');
        return $wBalanceSheetLabReport->getWidget();
    }

    function getBalanceSheetLabReportExport() {
        $wBalanceSheetLabReport = getCPWidgetObj('hms_balanceSheetLabReport');
        return $wBalanceSheetLabReport->model->getExportToExcel();
    }


    function getBalanceSheetImageReport() {
        $wBalanceSheetImageReport = getCPWidgetObj('hms_balanceSheetImageReport');
        return $wBalanceSheetImageReport->getWidget();
    }

    function getBalanceSheetImageReportExport() {
        $wBalanceSheetImageReport = getCPWidgetObj('hms_balanceSheetImageReport');
        return $wBalanceSheetImageReport->model->getExportToExcel();
    }

    function getBalanceSheetPharmacyReport() {
        $wBalanceSheetPharmacyReport = getCPWidgetObj('hms_balanceSheetPharmacyReport');
        return $wBalanceSheetPharmacyReport->getWidget();
    }

    function getBalanceSheetPharmacyReportExport() {
        $wBalanceSheetPharmacyReport = getCPWidgetObj('hms_balanceSheetPharmacyReport');
        return $wBalanceSheetLabReport->model->getExportToExcel();
    }

    function getVaccinationReport() {
        $wVaccinationReport = getCPWidgetObj('hms_vaccinationReport');
        return $wVaccinationReport->getWidget();
    }

    function getVaccinationReportExport() {
        $wVaccinationReport = getCPWidgetObj('hms_vaccinationReport');
        return $wVaccinationReport->model->getExportToExcel();
    }

    function getSupplierOutstandingReport() {
        $wSupplierOutstandingReport = getCPWidgetObj('hms_supplierOutstandingReport');
        return $wSupplierOutstandingReport->getWidget();
    }

    function getSupplierOutstandingReportExport() {
        $wSupplierOutstandingReport = getCPWidgetObj('hms_supplierOutstandingReport');
        return $wSupplierOutstandingReport->model->getExportToExcel();
    }

    function getPharmacyDailySales() {
        $wPharmacyDailySales = getCPWidgetObj('hms_pharmacyDailySales');
        return $wPharmacyDailySales->getWidget();
    }

    function getPharmacyDailySalesExport() {
        $wPharmacyDailySales = getCPWidgetObj('hms_pharmacyDailySales');
        return $wPharmacyDailySales->model->getExportToExcel();
    }

    function getMfgCompanyReport() {
        $wMfgCompanyReport = getCPWidgetObj('hms_mfgCompanyReport');
        return $wMfgCompanyReport->getWidget();
    }

    function getMfgCompanyReportExport() {
        $wMfgCompanyReport = getCPWidgetObj('hms_mfgCompanyReport');
        return $wMfgCompanyReport->model->getExportToExcel();
    }

    function getMOLReport() {
        $wMOLReport = getCPWidgetObj('hms_mOLReport');
        return $wMOLReport->getWidget();
    }

    function getMOLReportExport() {
        $wMOLReport = getCPWidgetObj('hms_mOLReport');
        return $wMOLReport->model->getExportToExcel();
    }

    function getProductSalesReport() {
        $wProductSalesReport = getCPWidgetObj('hms_productSalesReport');
        return $wProductSalesReport->getWidget();
    }

    function getProductSalesReportExport() {
        $wProductSalesReport = getCPWidgetObj('hms_productSalesReport');
        return $wProductSalesReport->model->getExportToExcel();
    }

    function getStockTransferReport() {
        $wStockTransferReport = getCPWidgetObj('hms_stockTransferReport');
        return $wStockTransferReport->getWidget();
    }

    function getStockTransferReportExport() {
        $wStockTransferReport = getCPWidgetObj('hms_stockTransferReport');
        return $wStockTransferReport->model->getExportToExcel();
    }

    function getInternalStockTransfer() {
        $wInternalStockTransfer = getCPWidgetObj('hms_internalStockTransfer');
        return $wInternalStockTransfer->getWidget();
    }

    function getInternalStockTransferExport() {
        $wInternalStockTransfer = getCPWidgetObj('hms_internalStockTransfer');
        return $wInternalStockTransfer->model->getExportToExcel();
    }

    function getDrugUsageReport() {
        $wDrugUsageReport = getCPWidgetObj('hms_drugUsageReport');
        return $wDrugUsageReport->getWidget();
    }

    function getDrugUsageReportExport() {
        $wDrugUsageReport = getCPWidgetObj('hms_drugUsageReport');
        return $wDrugUsageReport->model->getExportToExcel();
    }

    function getExpiringMedicineReport() {
        $wExpiringMedicineReport = getCPWidgetObj('hms_expiringMedicineReport');
        return $wExpiringMedicineReport->getWidget();
    }

    function getExpiringMedicineReportExport() {
        $wExpiringMedicineReport = getCPWidgetObj('hms_expiringMedicineReport');
        return $wExpiringMedicineReport->model->getExportToExcel();
    }

    function getAdjustStockReport() {
        $wAdjustStockReport = getCPWidgetObj('hms_adjustStockReport');
        return $wAdjustStockReport->getWidget();
    }

    function getAdjustStockReportExport() {
        $wAdjustStockReport = getCPWidgetObj('hms_adjustStockReport');
        return $wAdjustStockReport->model->getExportToExcel();
    }
    function getAdjustStockSummaryReport() {
        $wAdjustStockSummaryReport = getCPWidgetObj('hms_adjustStockSummaryReport');
        return $wAdjustStockSummaryReport->getWidget();
    }

    function getAdjustStockSummaryReportExport() {
        $wAdjustStockSummaryReport = getCPWidgetObj('hms_adjustStockSummaryReport');
        return $wAdjustStockSummaryReport->model->getExportToExcel();
    }
}