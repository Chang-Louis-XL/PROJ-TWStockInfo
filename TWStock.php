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
        // // 設置預設股票代號
        $code = isset($_POST['code']) ? htmlspecialchars($_POST['code']) : '0050';

        if ($code) {
            try {
                // 函數：嘗試獲取股票資料
                function getStockData($code, $market)
                {
                    $url = "https://mis.twse.com.tw/stock/api/getStockInfo.jsp?ex_ch={$market}_{$code}.tw&json=1&delay=0&_=" . time() . "&lang=zh_tw";
                    $response = @file_get_contents($url);

                    if ($response === false) {
                        return null;
                    }

                    return json_decode($response);
                }

                // 先試上市
                $stock = getStockData($code, 'tse');

                // 如果上市找不到或資料無效，試上櫃
                if ($stock === null || !isset($stock->msgArray[0]->n)) {
                    $stock = getStockData($code, 'otc');

                    // 如果上櫃也找不到或資料無效
                    if ($stock === null || !isset($stock->msgArray[0]->n)) {
                        throw new Exception("找不到此股票資料");
                    }
                }

                // 檢查是否有最新成交價
                if (
                    !isset($stock->msgArray[0]->z) || $stock->msgArray[0]->z === '-'
                ) {
                    throw new Exception("請間隔10秒後再次查詢");
                }


                $stockInfo = $stock->msgArray[0];
                echo $stockInfo->z;
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
            if ($('#priceChart').length) {
                const chart = echarts.init($('#priceChart')[0]);

                let chartData = [];
                let labels = [];

                try {
                    <?php
                    if (isset($stockInfo)) {
                        $dataPoints = [
                            ['o', '開盤'],
                            ['h', '最高'],
                            ['l', '最低'],
                            ['z', '現價']
                        ];

                        foreach ($dataPoints as $point) {
                            $value = isset($stockInfo->{$point[0]}) ? $stockInfo->{$point[0]} : null;
                            if ($value !== null && $value !== '' && is_numeric($value)) {
                                // 過濾掉非數字和無效值
                                $cleanValue = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                                if ($cleanValue !== false) {
                                    echo "chartData.push(" . floatval($cleanValue) . ");";
                                    echo "labels.push('" . $point[1] . "');";
                                }
                            }
                        }
                    }
                    ?>

                    // 確保數據陣列中沒有無效值
                    chartData = chartData.filter(value => !isNaN(value) && value !== null && value !== '');

                    const option = {
                        title: {
                            text: '股價走勢'
                        },
                        tooltip: {
                            trigger: 'axis',
                            formatter: function(params) {
                                if (params[0] && typeof params[0].value === 'number') {
                                    return params[0].name + ': ' + params[0].value.toFixed(2);
                                }
                                return '';
                            }
                        },
                        xAxis: {
                            type: 'category',
                            data: labels
                        },
                        yAxis: {
                            type: 'value',
                            scale: true,
                            // 不標數字的寫法
                            // axisLabel: {
                            // formatter: '{value}'
                            //  } 

                            axisLabel: {
                                formatter: function(value) {
                                    return value.toFixed(2);
                                }
                            }
                        },
                        series: [{
                            data: chartData,
                            type: 'line',
                            smooth: true,
                            label: {
                                show: true,
                                formatter: function(params) {
                                    if (typeof params.value === 'number') {
                                        return params.value.toFixed(2);
                                    }
                                    return '';
                                }
                            },
                            connectNulls: true // 連接空值點
                        }]
                    };

                    // 只在有有效數據時才渲染圖表
                    if (chartData.length > 0) {
                        chart.setOption(option);

                        // 添加視窗調整時自動重置圖表大小的功能
                        window.addEventListener('resize', function() {
                            chart.resize();
                        });
                    } else {
                        $('#priceChart').html('<div class="alert alert-info">暫無股價數據</div>');
                    }
                } catch (error) {
                    console.error('圖表初始化錯誤:', error);
                    $('#priceChart').html('<div class="alert alert-danger">圖表載入失敗</div>');
                }
            }
        });
    </script>
</body>

</html>