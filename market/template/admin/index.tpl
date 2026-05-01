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
                  <option value="0">待审核</option>
                  <option value="1">上架中</option>
                  <option value="2">已售出</option>
                  <option value="3">已下架</option>
                </select>
                <input type="text" class="form-control" id="keywordInput" placeholder="搜索标题">
                <btn class="btn btn-primary w-xs" id="searchBtn"><i class="fas fa-search"></i> 搜索</btn>
              </div>
            </div>

            <div class="table-body table-responsive">
              <table class="table table-bordered table-hover">
                <thead class="thead-light">
                  <tr>
                    <th class="center">ID</th>
                    <th>标题</th>
                    <th>卖家</th>
                    <th>售价</th>
                    <th>产品</th>
                    <th>到期时间</th>
                    <th class="center">推荐</th>
                    <th class="center">状态</th>
                    <th class="center">操作</th>
                  </tr>
                </thead>
                <tbody id="listTbody">
                  <tr><td colspan="9" class="text-center">加载中...</td></tr>
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

function loadList(page) {
  var status = $('#statusFilter').val();
  var keyword = $('#keywordInput').val();
  $.ajax({
    type: 'GET',
    url: '{:shd_addon_url("market://AdminIndex/getList")}',
    data: { page: page, limit: currentLimit, status: status, keyword: keyword },
    dataType: 'json',
    success: function (res) {
      if (res.status != 200) return;
      var data = res.data;
      var html = '';
      if (data.list.length == 0) {
        html = '<tr><td colspan="9" class="text-center">暂无数据</td></tr>';
      }
      $.each(data.list, function (i, v) {
        var featuredIcon = v.is_featured == '1'
          ? '<i class="fa fa-star text-warning"></i>'
          : '<i class="fa fa-star text-muted"></i>';
        var featuredBtn = v.is_featured == '1'
          ? '<a class="btn btn-link text-warning" onclick="toggleFeature(' + v.id + ')">取消推荐</a>'
          : '<a class="btn btn-link" onclick="toggleFeature(' + v.id + ')">推荐</a>';

        var actions = '';
        if (v.status == '0') {
          actions += '<a class="btn btn-link green" onclick="doAudit(' + v.id + ',\'pass\')">通过</a>';
          actions += '<a class="btn btn-link red" onclick="doAudit(' + v.id + ',\'reject\')">驳回</a>';
        }
        if (v.status != '2') {
          actions += '<a class="btn btn-link red" onclick="doDelete(' + v.id + ')">删除</a>';
        }

        var statusBadge = '';
        if (v.status == '0') statusBadge = '<span class="badge badge-warning">' + (v.status_text || '待审核') + '</span>';
        else if (v.status == '1') statusBadge = '<span class="badge badge-success">' + (v.status_text || '上架中') + '</span>';
        else if (v.status == '2') statusBadge = '<span class="badge badge-info">' + (v.status_text || '已售出') + '</span>';
        else if (v.status == '3') statusBadge = '<span class="badge badge-secondary">' + (v.status_text || '已下架') + '</span>';

        html += '<tr>';
        html += '<td class="center">' + v.id + '</td>';
        html += '<td><a href="#" title="' + (v.description || '') + '">' + v.title + '</a></td>';
        html += '<td>' + (v.seller || '') + '</td>';
        html += '<td>' + v.sale_price + '</td>';
        html += '<td>' + (v.product_name || '') + '</td>';
        html += '<td>' + (v.nextduedate ? new Date(v.nextduedate * 1000).toLocaleDateString() : '') + '</td>';
        html += '<td class="center">' + featuredIcon + ' ' + featuredBtn + '</td>';
        html += '<td class="center">' + statusBadge + '</td>';
        html += '<td>' + actions + '</td>';
        html += '</tr>';
      });
      $('#listTbody').html(html);
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
  html += '<li class="page-item ' + (page <= 1 ? 'disabled' : '') + '"><a class="page-link" href="javascript:;" onclick="loadList(' + (page - 1) + ')">上一页</a></li>';
  for (var i = 1; i <= totalPages; i++) {
    html += '<li class="page-item ' + (i == page ? 'active' : '') + '"><a class="page-link" href="javascript:;" onclick="loadList(' + i + ')">' + i + '</a></li>';
    if (i >= 10) break;
  }
  html += '<li class="page-item ' + (page >= totalPages ? 'disabled' : '') + '"><a class="page-link" href="javascript:;" onclick="loadList(' + (page + 1) + ')">下一页</a></li>';
  html += '</ul>';
  $('#paginationNav').html(html);
}

function doAudit(id, action) {
  var actName = action == 'pass' ? '通过' : '驳回';
  layer.confirm('确定要' + actName + '这个商品吗？', {
    btn: ['确定', '取消']
  }, function () {
    $.ajax({
      type: 'POST',
      url: '{:shd_addon_url("market://AdminIndex/audit")}',
      data: { id: id, action: action },
      dataType: 'json',
      success: function (res) {
        if (res.status == 200) {
          layer.msg(res.msg, {icon: 1, time: 1500});
          setTimeout(function () { loadList(currentPage); }, 1500);
        } else {
          layer.msg(res.msg, {icon: 5});
        }
      }
    });
  });
}

function toggleFeature(id) {
  $.ajax({
    type: 'POST',
    url: '{:shd_addon_url("market://AdminIndex/feature")}',
    data: { id: id },
    dataType: 'json',
    success: function (res) {
      if (res.status == 200) {
        layer.msg(res.msg, {icon: 1, time: 1500});
        setTimeout(function () { loadList(currentPage); }, 1500);
      } else {
        layer.msg(res.msg, {icon: 5});
      }
    }
  });
}

function doDelete(id) {
  layer.confirm('确定要删除这个商品吗？', {
    btn: ['确定', '取消']
  }, function () {
    $.ajax({
      type: 'POST',
      url: '{:shd_addon_url("market://AdminIndex/delete")}',
      data: { id: id },
      dataType: 'json',
      success: function (res) {
        if (res.status == 200) {
          layer.msg(res.msg, {icon: 1, time: 1500});
          setTimeout(function () { loadList(currentPage); }, 1500);
        } else {
          layer.msg(res.msg, {icon: 5});
        }
      }
    });
  });
}

$(function () {
  loadList(1);
  $('#searchBtn').on('click', function () { loadList(1); });
  $('#keywordInput').on('keypress', function (e) { if (e.which == 13) loadList(1); });
  $('#statusFilter').on('change', function () { loadList(1); });
});
</script>
