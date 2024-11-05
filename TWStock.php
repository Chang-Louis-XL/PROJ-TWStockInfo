<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>台股即時查詢系統</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .stock-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .price-up {
            color: #ff4d4d;
        }

        .price-down {
            color: #00b33c;
        }

        .search-box {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .chart-container {
            height: 300px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <h1 class="text-center mb-5">
            <i class="fas fa-chart-line"></i>
            台股即時查詢系統
        </h1>

        <div class="search-box">
            <form action="" method="post" class="row g-3 align-items-center justify-content-center">
                <!-- col-auto 的寬度會根據內容變化 -->
                <div class="col-auto">
                    <div class="input-group">
                        <input type="text" class="form-control" name="code" placeholder="請輸入股票代號"
                            required value="<?php echo isset($_POST['code']) ? htmlspecialchars($_POST['code']) : '0050'; ?>">
                        <!--
                            htmlspecialchars 是 PHP 中的一個安全性處理函式，用來防止 XSS（跨網站指令碼）攻擊， 將特殊字符轉換為 HTML 實體。
                        -->
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> 查詢
                    </button>
                </div>
            </form>
        </div>

        <?php
        // 設置預設股票代號
        $code = isset($_POST['code']) ? htmlspecialchars($_POST['code']) : '0050';

        if (isset($_POST['code'])) {
            try {
                // 使用 $code 變數來生成請求的 URL
                $stock = @file_get_contents("https://mis.twse.com.tw/stock/api/getStockInfo.jsp?ex_ch=tse_{$code}.tw&json=1&delay=0&_=" . time() . "&lang=zh_tw");

                if ($stock === false) {
                    // throw new Exception()是為了發出一個異常（異常提示）的語法
                    throw new Exception("無法取得股票資料");
                }

                $stock = json_decode($stock);

                if (!isset($stock->msgArray[0]->n)) {
                    throw new Exception("找不到此股票資料");
                }

                $stockInfo = $stock->msgArray[0];
                $priceChange = floatval($stockInfo->z) - floatval($stockInfo->y);
                $changePercentage = ($priceChange / floatval($stockInfo->y)) * 100;
                $priceClass = $priceChange >= 0 ? 'price-up' : 'price-down';
                $arrow = $priceChange >= 0 ? '▲' : '▼';
        ?>
                <div class="stock-card p-4 mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <h2><?php echo htmlspecialchars($stockInfo->n); ?>
                                <small class="text-muted"><?php echo htmlspecialchars($stockInfo->c); ?></small>
                            </h2>
                            <h3 class="<?php echo $priceClass; ?>">
                                <?php echo $stockInfo->z; ?>
                                <small>
                                    <!-- abs() 是 PHP 內建的函數，用來取得一個數字的絕對值（absolute value）。絕對值就是把負數轉成正數，而正數則保持不變。 -->
                                    <?php echo $arrow . ' ' .  number_format(abs($priceChange), 2); ?>
                                    <!-- number_format()自動加入千位分隔符號 (預設是逗號) -->
                                    (<?php echo number_format($changePercentage, 2); ?>%)
                                </small>
                            </h3>
                            <div class="row mt-4">
                                <div class="col-6">
                                    <p class="mb-1">開盤價</p>
                                    <h5><?php echo $stockInfo->o; ?></h5>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1">昨收價</p>
                                    <h5><?php echo $stockInfo->y; ?></h5>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1">最高價</p>
                                    <h5><?php echo $stockInfo->h; ?></h5>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1">最低價</p>
                                    <h5><?php echo $stockInfo->l; ?></h5>
                                </div>
                                <div class="col-12 mt-3">
                                    <p class="mb-1">成交量</p>
                                    <h5><?php echo number_format($stockInfo->v); ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container" id="priceChart"></div>
                        </div>
                    </div>
                </div>
        <?php


                // try {
                //     // 假設這是連接資料庫的程式碼
                //     $db = new PDO("mysql:host=localhost;dbname=test", "user", "wrong_password");
                // } catch (Exception $e) {
                //     // 如果連線失敗，可能會顯示：
                //     // <div class="alert alert-danger">SQLSTATE[28000] [1045] Access denied for user 'user'@'localhost'</div>
                //     echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
                // }


            } catch (Exception $e) {
                echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
            }
        }
        ?>

    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>
    <script>
        $(document).ready(function() {
            // 如果有圖表容器，初始化圖表
            // 檢查是否存在 ID 為 'priceChart' 的元素
            if ($('#priceChart').length) {
                // 初始化 ECharts 圖表，並指定在 'priceChart' 元素中繪製
                const chart = echarts.init($('#priceChart')[0]);

                // 定義圖表的配置選項
                const option = {
                    // 圖表標題設定
                    title: {
                        text: '股價走勢'
                    },

                    // 提示框（滑鼠移到數據點時顯示的資訊）配置
                    tooltip: {
                        trigger: 'axis' // 觸發方式為：滑鼠移到軸線上
                    },

                    // X軸配置
                    xAxis: {
                        type: 'category', // 類別型，用於顯示文字類型的資料
                        data: ['開盤', '最高', '最低', '現價'] // X軸的標籤
                    },

                    // Y軸配置
                    yAxis: {
                        type: 'value', // 數值型
                        scale: true // 自動調整刻度，不會從0開始
                    },

                    // 數據系列配置
                    series: [{
                        // 資料數組，從PHP獲取股票資訊
                        data: [
                            <?php
                            if (isset($stockInfo)) {
                                echo $stockInfo->o . ',';  // 開盤價
                                echo $stockInfo->h . ',';  // 最高價
                                echo $stockInfo->l . ',';  // 最低價
                                echo $stockInfo->z;        // 現價
                            }
                            ?>
                        ],
                        type: 'line', // 圖表類型為線圖
                        smooth: true // 使用平滑曲線
                    }]
                };

                // 將配置應用到圖表
                chart.setOption(option);
            }
        });
    </script>
</body>

</html>