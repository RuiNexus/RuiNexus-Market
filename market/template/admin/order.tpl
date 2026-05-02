<section class="admin-main">
  <div class="container-fluid">
    <div class="page-container">
      <div class="card">
        <div class="card-body">
          <div class="card-title row">
            <div style="padding:0 15px;">{$Title}</div>
            <div class="col-lg-8 col-md-12 col-sm-12">
              {foreach $PluginsAdminMenu as $v}
                {if $v['custom']}
                  <span class="ml-2"><a class="h5" href="{$v.url}" target="_blank">{$v.name}</a></span>
                {else/}
                  <span class="ml-2"><a class="h5" href="{$v.url}">{$v.name}</a></span>
                {/if}
              {/foreach}
            </div>
          </div>

          <div class="table-container">
            <div class="table-header">
              <div class="table-tools">
                <select class="form-control" id="statusFilter">
                  <option value="">全部状态</option>
                  <option value="0">待付款</option>
                  <option value="1">已付款</option>
                  <option value="2">已转移</option>
                  <option value="3">已完成</option>
                  <option value="4">已取消</option>
                  <option value="5">退款中</option>
                  <option value="6">已退款</option>
                </select>
                <btn class="btn btn-primary w-xs" id="searchBtn"><i class="fas fa-search"></i> 筛选</btn>
              </div>
            </div>

            <div class="table-body table-responsive">
              <table class="table table-bordered table-hover">
                <thead class="thead-light">
                  <tr>
                    <th class="center">订单ID</th>
                    <th>商品标题</th>
                    <th>卖家</th>
                    <th>买家</th>
                    <th>金额</th>
                    <th>手续费</th>
                    <th class="center">支付方式</th>
                    <th class="center">状态</th>
                    <th class="center">创建时间</th>
                    <th class="center">操作</th>
                  </tr>
                </thead>
                <tbody id="orderTbody">
                  <tr><td colspan="10" class="text-center">加载中...</td></tr>
                </tbody>
              </table>
            </div>

            <div class="table-footer">
              <div class="table-pagination">
                <div class="table-pageinfo" id="pageInfo">每页显示 20 条数据</div>
                <nav id="paginationNav"></nav>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script src="https://cdn.bootcdn.net/ajax/libs/layer/3.5.1/layer.js"></script>
<script type="text/javascript">
var currentPage = 1;
var currentLimit = 20;

function loadOrders(page) {
  var status = $('#statusFilter').val();
  $.ajax({
    type: 'GET',
    url: '{:shd_addon_url("market://AdminIndex/getOrders")}',
    data: { page: page, limit: currentLimit, status: status },
    dataType: 'json',
    success: function (res) {
      if (res.status != 200) return;
      var data = res.data;
      var html = '';
      if (data.list.length == 0) {
        html = '<tr><td colspan="10" class="text-center">暂无数据</td></tr>';
      }
      $.each(data.list, function (i, v) {
        var statusBadge = '';
        if (v.status == '0') statusBadge = '<span class="badge badge-warning">' + (v.status_text || '待付款') + '</span>';
        else if (v.status == '1') statusBadge = '<span class="badge badge-info">' + (v.status_text || '已付款') + '</span>';
        else if (v.status == '2') statusBadge = '<span class="badge badge-primary">' + (v.status_text || '已转移') + '</span>';
        else if (v.status == '3') statusBadge = '<span class="badge badge-success">' + (v.status_text || '已完成') + '</span>';
        else if (v.status == '4') statusBadge = '<span class="badge badge-secondary">' + (v.status_text || '已取消') + '</span>';
        else if (v.status == '5') statusBadge = '<span class="badge badge-danger">' + (v.status_text || '退款中') + '</span>';
        else if (v.status == '6') statusBadge = '<span class="badge badge-dark">' + (v.status_text || '已退款') + '</span>';

        html += '<tr>';
        html += '<td class="center">' + v.id + '</td>';
        html += '<td>' + (v.listing_title || '已删除') + '</td>';
        html += '<td>' + (v.seller || '') + '</td>';
        html += '<td>' + (v.buyer || '') + '</td>';
        html += '<td>' + v.amount + '</td>';
        html += '<td>' + v.fee + '</td>';
        html += '<td class="center">' + v.pay_type_text + '</td>';
        html += '<td class="center">' + statusBadge + '</td>';
        html += '<td class="center">' + (v.create_time ? new Date(v.create_time * 1000).toLocaleString() : '') + '</td>';
        html += '<td class="center">';
        if (v.status == '0') {
          html += '<button class="btn btn-danger btn-xs" onclick="cancelOrder(' + v.id + ')">取消</button>';
        }
        html += '</td>';
        html += '</tr>';
      });
      $('#orderTbody').html(html);
      $('#pageInfo').text('共 ' + data.total + ' 条，每页 ' + currentLimit + ' 条');
      renderPagination(data.total, data.page, data.limit);
    }
  });
}

function renderPagination(total, page, limit) {
  var totalPages = Math.ceil(total / limit);
  if (totalPages <= 1) {
    $('#paginationNav').html('');
    return;
  }
  var html = '<ul class="pagination">';
  html += '<li class="page-item ' + (page <= 1 ? 'disabled' : '') + '"><a class="page-link" href="javascript:;" onclick="loadOrders(' + (page - 1) + ')">上一页</a></li>';
  for (var i = 1; i <= totalPages; i++) {
    html += '<li class="page-item ' + (i == page ? 'active' : '') + '"><a class="page-link" href="javascript:;" onclick="loadOrders(' + i + ')">' + i + '</a></li>';
    if (i >= 10) break;
  }
  html += '<li class="page-item ' + (page >= totalPages ? 'disabled' : '') + '"><a class="page-link" href="javascript:;" onclick="loadOrders(' + (page + 1) + ')">下一页</a></li>';
  html += '</ul>';
  $('#paginationNav').html(html);
}

function cancelOrder(orderId) {
  layer.confirm('确认取消该订单？取消后商品将自动解锁。', {
    btn: ['确认取消', '再想想'],
    title: '取消订单',
    skin: 'layui-layer-lan'
  }, function (index) {
    layer.close(index);
    $.ajax({
      type: 'POST',
      url: '{:shd_addon_url("market://AdminIndex/cancelOrder")}',
      data: { order_id: orderId },
      dataType: 'json',
      success: function (res) {
        if (res.status == 200) {
          layer.msg(res.msg, { icon: 1 });
          loadOrders(currentPage);
        } else {
          layer.msg(res.msg, { icon: 2 });
        }
      },
      error: function () {
        layer.msg('操作失败，请重试', { icon: 2 });
      }
    });
  });
}

$(function () {
  loadOrders(1);
  $('#searchBtn').on('click', function () { loadOrders(1); });
  $('#statusFilter').on('change', function () { loadOrders(1); });
});
</script>
