import { useState, useEffect, useMemo } from 'react';
import { Head } from '@inertiajs/react';
import { startOfMonth, endOfMonth } from 'date-fns';
import { DateRange } from 'react-day-picker';

import Authenticated from '@/Layouts/Authenticated';
import { DatePickerWithRange } from '@/components/ui/date-picker-with-range';
import SpendingTotal from '@/components/Domain/SpendingTotal';
import TypeFilterTabs from '@/components/Domain/TypeFilterTabs';
import CategoryBarChart from '@/components/Domain/CategoryBarChart';
import TypeDonutChart from '@/components/Domain/TypeDonutChart';
import SpendingTransactionTable from '@/components/Domain/SpendingTransactionTable';
import RecordTransactionButton from '@/components/Domain/RecordTransactionButton';
import { getAllBrands } from '@/Api/brands';
import { getAllCategories } from '@/Api/categories';

// Original Financial Metrics Components
import ValueMetric from '@/components/Domain/ValueMetric';
import TrendMetric from '@/components/Domain/TrendMetric';
import PartitionMetric from '@/components/Domain/PartitionMetric';
import CirclePackMetric from '@/components/Domain/CirclePackMetric';
import SectionDivider from '@/components/Global/SectionDivider';
import Budgets from '@/components/Domain/Budgets';
import NoContent from '@/components/Global/NoContent';

// Phase 6 Widgets
import CashFlowWidget from '@/components/Domain/CashFlowWidget';
import SavingsRateWidget from '@/components/Domain/SavingsRateWidget';
import FinancialHealthScoreWidget from '@/components/Domain/FinancialHealthScoreWidget';
import SpendingAlertsWidget from '@/components/Domain/SpendingAlertsWidget';
import EmergencyFundWidget from '@/components/Domain/EmergencyFundWidget';
import TopExpensesWidget from '@/components/Domain/TopExpensesWidget';
import RecurringExpensesWidget from '@/components/Domain/RecurringExpensesWidget';
import QuickActionsWidget from '@/components/Domain/QuickActionsWidget';
import FinancialProjectionChart from '@/components/Domain/FinancialProjectionChart';

export default function Dashboard({ auth, hasData }: any) {
    const [allBrands, setAllBrands] = useState<any[]>([]);
    const [allCategories, setAllCategories] = useState<any[]>([]);
    const [filterType, setFilterType] = useState<string>('all');
    const [refreshKey, setRefreshKey] = useState(0);
    const [activeView, setActiveView] = useState<'spending' | 'finance'>('spending');
    const [dateRange, setDateRange] = useState<DateRange>({
        from: startOfMonth(new Date()),
        to: endOfMonth(new Date()),
    });

    useEffect(() => {
        Promise.all([
            getAllBrands(),
            getAllCategories()
        ]).then(([{ data: brands }, { data: categories }]) => {
            setAllBrands(brands.allBrands);
            setAllCategories(categories.allCategories);
        }).catch(console.error);
    }, []);

    const handleDateChange = (newDateRange: DateRange | undefined) => {
        if (newDateRange?.from && newDateRange?.to) {
            setDateRange(newDateRange);
        }
    };

    const handleTransactionSuccess = () => {
        setRefreshKey(prev => prev + 1);
    };

    const categoryRelation = useMemo(() => ({
        data: allCategories,
        display_using: 'name',
        foreign_key: 'id'
    }), [allCategories]);

    const categoryRelationForBrands = useMemo(() => ({
        data: allCategories,
        display_using: 'name',
        foreign_key: 'category_id'
    }), [allCategories]);

    const brandRelation = useMemo(() => ({
        data: allBrands,
        display_using: 'name',
        foreign_key: 'id'
    }), [allBrands]);

    const header = (
        <div className="flex items-center justify-between w-full">
            <h2>Dashboard</h2>
            <div className="flex items-center gap-2">
                <DatePickerWithRange
                    onDateChange={handleDateChange}
                    initialDate={dateRange}
                />
                <RecordTransactionButton
                    brands={allBrands}
                    onSuccess={handleTransactionSuccess}
                />
            </div>
        </div>
    );

    return (
        <Authenticated auth={auth} header={header}>
            <Head title="Dashboard" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* View Toggle Tabs */}
                    <div className="flex gap-1 bg-gray-100 p-1 rounded-lg w-fit mb-6">
                        <button
                            onClick={() => setActiveView('spending')}
                            className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
                                activeView === 'spending'
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900'
                            }`}
                        >
                            Spending Tracker
                        </button>
                        <button
                            onClick={() => setActiveView('finance')}
                            className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${
                                activeView === 'finance'
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900'
                            }`}
                        >
                            Financial Overview
                        </button>
                    </div>

                    {activeView === 'spending' ? (
                        /* Spending Tracker View */
                        <>
                            {/* Header Section */}
                            <div className="mb-8">
                                <div className="flex items-start justify-between">
                                    <div>
                                        <h1 className="text-3xl font-bold text-gray-900">Spending Tracker</h1>
                                        <p className="mt-1 text-gray-500">Personal & household expenses</p>
                                    </div>
                                    <SpendingTotal 
                                        key={`total-${refreshKey}`}
                                        dateRange={dateRange} 
                                        type={filterType} 
                                    />
                                </div>

                                {/* Filter Tabs */}
                                <div className="mt-6">
                                    <TypeFilterTabs 
                                        value={filterType} 
                                        onChange={setFilterType} 
                                    />
                                </div>
                            </div>

                            {/* Charts Section */}
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                                <CategoryBarChart 
                                    key={`category-${refreshKey}`}
                                    dateRange={dateRange} 
                                    type={filterType} 
                                />
                                <TypeDonutChart 
                                    key={`type-${refreshKey}`}
                                    dateRange={dateRange} 
                                />
                            </div>

                            {/* Transactions Table */}
                            <div>
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">Recent Transactions</h2>
                                <SpendingTransactionTable 
                                    key={`transactions-${refreshKey}`}
                                    dateRange={dateRange} 
                                    type={filterType}
                                />
                            </div>
                        </>
                    ) : (
                        /* Financial Overview View */
                        <>
                            <Budgets key={`budgets-${refreshKey}`} />

                            {!hasData && <NoContent body="No enough data to show reports" />}

                            {hasData && (
                                <div className="grid grid-cols-1 gap-4 mt-6">
                                    {/* Net Worth - Full Width Trend */}
                                    <div className="w-full">
                                        <TrendMetric
                                            key={`netWorthTrend-${refreshKey}`}
                                            name="Net Worth Over Time"
                                            metric="netWorthTrend"
                                            dateRange={dateRange}
                                        />
                                    </div>

                                    <div className="w-full grid grid-cols-1 md:grid-cols-3 gap-4"
                                    >
                                        <ValueMetric
                                            key={`totalCash-${refreshKey}`}
                                            name="Total Cash"
                                            metric="totalCash"
                                            helpText="The available cash = income - (expenses + savings + investments)"
                                            dateRange={dateRange}
                                        />
                                        <ValueMetric
                                            key={`totalSavings-${refreshKey}`}
                                            name="Total Savings"
                                            metric="totalSavings"
                                            dateRange={dateRange}
                                        />
                                        <ValueMetric
                                            key={`totalInvestment-${refreshKey}`}
                                            name="Total Investment"
                                            metric="totalInvestment"
                                            dateRange={dateRange}
                                        />
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <ValueMetric
                                            key={`totalIncome-${refreshKey}`}
                                            name="Total Income"
                                            metric="totalIncome"
                                            dateRange={dateRange}
                                        />
                                        <ValueMetric
                                            key={`totalExpenses-${refreshKey}`}
                                            name="Total Expenses"
                                            metric="totalExpenses"
                                            dateRange={dateRange}
                                        />

                                        <TrendMetric
                                            key={`totalIncomeTrend-${refreshKey}`}
                                            name="Income Over Time"
                                            metric="totalIncomeTrend"
                                            dateRange={dateRange}
                                        />
                                        <TrendMetric
                                            key={`totalExpensesTrend-${refreshKey}`}
                                            name="Spending Over Time"
                                            metric="totalExpensesTrend"
                                            dateRange={dateRange}
                                        />
                                    </div>

                                    {/* Phase 6: Financial Health Widgets */}
                                    <SectionDivider title="Financial Health" />

                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <CashFlowWidget
                                            key={`cashFlow-${refreshKey}`}
                                            dateRange={dateRange}
                                        />
                                        <SavingsRateWidget
                                            key={`savingsRate-${refreshKey}`}
                                            dateRange={dateRange}
                                        />
                                        <EmergencyFundWidget
                                            key={`emergencyFund-${refreshKey}`}
                                            dateRange={dateRange}
                                        />
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <FinancialHealthScoreWidget
                                            key={`financialHealth-${refreshKey}`}
                                            dateRange={dateRange}
                                        />
                                        <SpendingAlertsWidget
                                            key={`spendingAlerts-${refreshKey}`}
                                            dateRange={dateRange}
                                        />
                                    </div>

                                    {/* Phase 6: Insights & Actions */}
                                    <SectionDivider title="Insights & Actions" />

                                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                        <div className="lg:col-span-2">
                                            <TopExpensesWidget
                                                key={`topExpenses-${refreshKey}`}
                                                dateRange={dateRange}
                                                limit={5}
                                            />
                                        </div>
                                        <div className="space-y-4">
                                            <RecurringExpensesWidget
                                                key={`recurringExpenses-${refreshKey}`}
                                                dateRange={dateRange}
                                            />
                                            <QuickActionsWidget
                                                key={`quickActions-${refreshKey}`}
                                                onAddTransaction={() => {
                                                    // Trigger the RecordTransactionButton
                                                    const btn = document.querySelector('[data-testid="record-transaction-btn"]') as HTMLElement;
                                                    btn?.click();
                                                }}
                                            />
                                        </div>
                                    </div>

                                    {/* Phase 6: Financial Forecast */}
                                    <SectionDivider title="Financial Forecast" />

                                    <div className="w-full">
                                        <FinancialProjectionChart
                                            key={`financialProjection-${refreshKey}`}
                                            dateRange={dateRange}
                                        />
                                    </div>

                                    <SectionDivider title="Categories Analytics" />

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <PartitionMetric
                                            key={`incomePerCategory-${refreshKey}`}
                                            name="Income Sources"
                                            metric="incomePerCategory"
                                            show_currency={true}
                                            dateRange={dateRange}
                                        />
                                        <PartitionMetric
                                            key={`expensesPerCategory-${refreshKey}`}
                                            name="Spending by Category"
                                            metric="expensesPerCategory"
                                            show_currency={true}
                                            dateRange={dateRange}
                                        />

                                        <TrendMetric
                                            key={`totalPerCategoryTrend-${refreshKey}`}
                                            name="Overall Trend by Category"
                                            metric="totalPerCategoryTrend"
                                            relation={categoryRelation}
                                            dateRange={dateRange}
                                        />
                                        <TrendMetric
                                            key={`totalPerCategoryDailyTrend-${refreshKey}`}
                                            name="Daily Trend by Category"
                                            metric="totalPerCategoryDailyTrend"
                                            relation={categoryRelation}
                                            dateRange={dateRange}
                                            defaultToCurrentYear={false}
                                        />
                                    </div>

                                    <SectionDivider title="Brands Analytics" />

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <PartitionMetric
                                            key={`totalPerBrand-${refreshKey}`}
                                            name="Spending by Brand"
                                            metric="totalPerBrand"
                                            relation={categoryRelationForBrands}
                                            show_currency={true}
                                            dateRange={dateRange}
                                        />
                                        <TrendMetric
                                            key={`totalPerBrandTrend-${refreshKey}`}
                                            name="Overall Trend by Brand"
                                            metric="totalPerBrandTrend"
                                            relation={brandRelation}
                                            dateRange={dateRange}
                                        />
                                    </div>

                                    <SectionDivider title="Finance Visualization" />

                                    <div className="w-full">
                                        <CirclePackMetric
                                            key={`financeVisualizationCirclePackMetric-${refreshKey}`}
                                            name="Finance Visualization"
                                            metric="financeVisualizationCirclePackMetric"
                                            dateRange={dateRange}
                                        />
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </Authenticated>
    );
}
