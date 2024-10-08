<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Auth\Entities\User;
use Modules\Order\Entities\Order_items;
use Modules\Order\Entities\Orders;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Product\Entities\Product;
use Modules\Review\Entities\Review;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $title = 'Admin - Dashboard';
        // Lấy ngày bắt đầu và ngày kết thúc từ request, nếu không có thì sử dụng giá trị mặc định
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        $lastMonthEnd = Carbon::parse($startDate)->startOfDay();
        $currentMonthStart = Carbon::parse($endDate)->endOfDay();
        $lastMonthStart = Carbon::parse($startDate)->subDays(30)->startOfDay();

        // Tính tổng doanh thu trong khoảng thời gian được chọn
        $totalRevenueCurrentMonth = Orders::whereBetween('order_date', [$lastMonthEnd, $currentMonthStart])->sum('total_amount');
        $totalRevenueLastMonth = Orders::whereBetween('order_date', [$lastMonthStart, $lastMonthEnd])->sum('total_amount');

        // Tính tỷ lệ thay đổi doanh thu
        $rateRevenue = $totalRevenueLastMonth != 0 ? (($totalRevenueCurrentMonth - $totalRevenueLastMonth) / $totalRevenueLastMonth) * 100 : 0;

        // Tính tổng số đơn hàng
        $totalOrdersCurrentMonth = Orders::whereBetween('order_date', [$lastMonthEnd, $currentMonthStart])->count();
        $totalOrdersLastMonth = Orders::whereBetween('order_date', [$lastMonthStart, $lastMonthEnd])->count();

        // Tính tỷ lệ thay đổi số đơn hàng
        $rateOrders = $totalOrdersLastMonth != 0 ? (($totalOrdersCurrentMonth - $totalOrdersLastMonth) / $totalOrdersLastMonth) * 100 : 0;

        // Tính tổng số sản phẩm đã bán
        $totalProductsSoldCurrentMonth = Order_items::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.order_date', [$lastMonthEnd, $currentMonthStart])
            ->sum('order_items.quantity');
        $totalProductsSoldLastMonth = Order_items::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.order_date', [$lastMonthStart, $lastMonthEnd])
            ->sum('order_items.quantity');

        // Tính tỷ lệ thay đổi số lượng sản phẩm đã bán
        $rateProducts = $totalProductsSoldLastMonth != 0 ? (($totalProductsSoldCurrentMonth - $totalProductsSoldLastMonth) / $totalProductsSoldLastMonth) * 100 : 0;

        // Tính số lượng người dùng mới
        $newUsersCountCurrentMonth = User::whereBetween('created_at', [$lastMonthEnd, $currentMonthStart])->count();
        $newUsersCountLastMonth = User::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();

        // Tính tỷ lệ thay đổi số lượng người dùng mới
        $rateUsers = $newUsersCountLastMonth != 0 ? (($newUsersCountCurrentMonth - $newUsersCountLastMonth) / $newUsersCountLastMonth) * 100 : 0;

        // Lấy dữ liệu cho biểu đồ
        $currentMonthRevenueData = $this->getChartData('revenue', $lastMonthEnd, $currentMonthStart);
        $currentMonthOrdersData = $this->getChartData('new_orders', $lastMonthEnd, $currentMonthStart);
        $currentMonthSoldProductsData = $this->getChartData('sold_products', $lastMonthEnd, $currentMonthStart);
        $currentMonthNewCustomersData = $this->getChartData('new_customers', $lastMonthEnd, $currentMonthStart);
        $lastMonthRevenueData = $this->getChartData('revenue', $lastMonthStart, $lastMonthEnd);
        $lastMonthOrdersData = $this->getChartData('new_orders', $lastMonthStart, $lastMonthEnd);
        $lastMonthSoldProductsData = $this->getChartData('sold_products', $lastMonthStart, $lastMonthEnd);
        $lastMonthNewCustomersData = $this->getChartData('new_customers', $lastMonthStart, $lastMonthEnd);

        // Các dữ liệu khác để hiển thị trên trang
        $orders = Orders::with('items.product')
            ->whereBetween('order_date', [$lastMonthEnd, $currentMonthStart])
            ->get();

        // Tính tổng số lượng và doanh thu của từng sản phẩm
        $productQuantities = [];
        $productRevenues = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $productId = $item->product_id;
                $quantity = $item->quantity;
                $price = $item->price;

                if (isset($productQuantities[$productId])) {
                    $productQuantities[$productId] += $quantity;
                } else {
                    $productQuantities[$productId] = $quantity;
                }

                $revenue = $quantity * $price;
                if (isset($productRevenues[$productId])) {
                    $productRevenues[$productId] += $revenue;
                } else {
                    $productRevenues[$productId] = $revenue;
                }
            }
        }

        // Sắp xếp sản phẩm theo số lượng bán và lấy top 5 sản phẩm bán chạy nhất
        arsort($productQuantities);
        $topProductsByQuantity = array_slice($productQuantities, 0, 5, true);

        // Lấy thông tin chi tiết của các sản phẩm trong top 5 bán chạy nhất
        $topProductIdsByQuantity = array_keys($topProductsByQuantity);
        $topProductDetailsByQuantity = Product::with('primaryImage')
            ->whereIn('id', $topProductIdsByQuantity)
            ->select('id', 'product_name', 'product_code')
            ->get();

        foreach ($topProductDetailsByQuantity as $product) {
            $productId = $product->id;
            $product->sold_quantity = $topProductsByQuantity[$productId];
            $product->total_revenue = $product->sold_quantity * $productRevenues[$productId];
        }

        // Sắp xếp lại theo số lượng đã bán giảm dần
        $topProductDetailsByQuantity = $topProductDetailsByQuantity->sortByDesc(function ($product) {
            return $product->sold_quantity;
        })->values()->all();

        // Lấy 5 bình luận mới nhất
        $latestComments = Review::with('user', 'product')
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get();

        // Lấy các sản phẩm đã hết hàng
        $outOfStockProducts = Product::where('quantity', 0)->get();

        // Lấy các sản phẩm sắp hết hàng (dưới 10 sản phẩm)
        $lowStockProducts = Product::where('quantity', '<=', 10)->where('quantity', '>', 0)->get();

        return view('admin.dashboard.dashboard', compact(
            'totalRevenueCurrentMonth',
            'totalOrdersCurrentMonth',
            'totalProductsSoldCurrentMonth',
            'newUsersCountCurrentMonth',
            'rateRevenue',
            'rateOrders',
            'rateProducts',
            'rateUsers',
            'currentMonthRevenueData',
            'currentMonthOrdersData',
            'currentMonthSoldProductsData',
            'currentMonthNewCustomersData',
            'totalRevenueLastMonth',
            'totalOrdersLastMonth',
            'totalProductsSoldLastMonth',
            'newUsersCountLastMonth',
            'lastMonthRevenueData',
            'lastMonthOrdersData',
            'lastMonthSoldProductsData',
            'topProductDetailsByQuantity',
            'latestComments',
            'outOfStockProducts',
            'lowStockProducts',
            'title',
        ));
    }


    private function getChartData($type, $startDate, $endDate)
    {
        $dates = [];
        $data = [];
        $interval = CarbonPeriod::create($startDate, $endDate);
        foreach ($interval as $date) {
            $dates[] = $date->format('Y-m-d');
        }
        switch ($type) {
            case 'revenue':
                $results = Orders::selectRaw('DATE(order_date) as date, SUM(total_amount) as total')
                    ->whereBetween('order_date', [$startDate, $endDate])
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
                break;
            case 'new_orders':
                $results = Orders::selectRaw('DATE(order_date) as date, COUNT(*) as total')
                    ->whereBetween('order_date', [$startDate, $endDate])
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
                break;
            case 'sold_products':
                $results = Order_items::join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->selectRaw('DATE(orders.order_date) as date, SUM(order_items.quantity) as total')
                    ->whereBetween('orders.order_date', [$startDate, $endDate])
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
                break;
            case 'new_customers':
                $results = User::selectRaw('DATE(created_at) as date, COUNT(*) as total')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
                break;
            default:
                $results = collect();
                break;
        }
        foreach ($dates as $date) {
            $result = $results->firstWhere('date', $date);
            $data[] = $result ? $result->total : 0;
        }

        return json_encode([
            'labels' => $dates,
            'data' => $data,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function product()
    {
        return view('admin.product.product');
    }

    public function add_product()
    {
        return view('admin.category.add');
    }

    public function login_admin()
    {
        return view('admin.login.admin_login');
    }
    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('admin.product.product');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('admin::product.add');
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
