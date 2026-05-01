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
          <div class="help-block">
            管理员可直接将指定用户的产品发布到交易市场，无需审核直接上架
          </div>

          <div class="card-body px-5 mx-auto w-75">
            <div class="form-group row">
              <label class="col-sm-2 col-form-label">用户ID</label>
              <div class="col-sm-4">
                <div class="input-group">
                  <input type="number" class="form-control" id="uidInput" placeholder="输入用户ID" min="1">
                  <div class="input-group-append">
                    <button class="btn btn-primary" id="searchUserBtn"><i class="fas fa-search"></i> 查询</button>
                  </div>
                </div>
              </div>
              <div class="col-sm-6">
                <span id="userInfo" class="form-text"></span>
              </div>
            </div>
          </div>

          <div id="hostListArea" style="display:none;">
            <hr>
            <div class="table-body table-responsive">
              <table class="table table-bordered table-hover">
                <thead class="thead-light">
                  <tr>
                    <th class="center">主机ID</th>
                    <th>产品名称</th>
                    <th>域名/IP</th>
                    <th class="center">状态</th>
                    <th>售价</th>
                    <th class="center">操作</th>
                  </tr>
                </thead>
                <tbody id="hostTbody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script src="https://cdn.bootcdn.net/ajax/libs/layer/3.5.1/layer.js"></script>
<script type="text/javascript">
var currentUid = 0;

$('#searchUserBtn').on('click', function () {
  var uid = parseInt($('#uidInput').val());
  if (!uid || uid <= 0) {
    layer.msg('请输入有效的用户ID', {icon: 5});
    return;
  }
  currentUid = uid;
  loadHosts(uid);
});

$('#uidInput').on('keypress', function (e) {
  if (e.which == 13) $('#searchUserBtn').click();
});

function loadHosts(uid) {
  var btn = $('#searchUserBtn');
  btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 查询中...');

  $.ajax({
    type: 'GET',
    url: '{:shd_addon_url("market://AdminIndex/searchUserHosts")}',
    data: { uid: uid },
    dataType: 'json',
    success: function (res) {
      btn.prop('disabled', false).html('<i class="fas fa-search"></i> 查询');
      if (res.status != 200) {
        $('#hostListArea').hide();
        $('#userInfo').html('<span class="text-danger">' + res.msg + '</span>');
        return;
      }
      var data = res.data;
      $('#userInfo').html(
        '<span class="text-success"><strong>' + data.user.username + '</strong> (ID:' + data.user.id + ', ' + data.user.email + ')</span>'
      );

      var html = '';
      if (data.hosts.length == 0) {
        html = '<tr><td colspan="7" class="text-center">该用户暂无产品</td></tr>';
      }
      $.each(data.hosts, function (i, h) {
        var statusClass = h.domainstatus == 'Active' ? 'badge badge-success' : 'badge badge-secondary';
        var priceDefault = h.original_amount > 0 ? h.original_amount : '';

        if (h.can_publish) {
          html += '<tr>';
          html += '<td class="center">' + h.id + '</td>';
          html += '<td>' + (h.product_name || '--') + '</td>';
          html += '<td>' + (h.domain || h.dedicatedip || '--') + '</td>';
          html += '<td class="center"><span class="' + statusClass + '">' + h.domainstatus + '</span></td>';
          html += '<td style="width:140px;"><input type="number" class="form-control form-control-sm price-input" value="' + priceDefault + '" min="0.01" step="0.01" placeholder="售价"></td>';
          html += '<td class="center"><button class="btn btn-sm btn-primary publish-btn" data-hid="' + h.id + '">上架</button></td>';
          html += '</tr>';
        } else {
          html += '<tr class="table-secondary">';
          html += '<td class="center">' + h.id + '</td>';
          html += '<td>' + (h.product_name || '--') + '</td>';
          html += '<td>' + (h.domain || h.dedicatedip || '--') + '</td>';
          html += '<td class="center"><span class="' + statusClass + '">' + h.domainstatus + '</span></td>';
          html += '<td colspan="2" class="text-muted">' + h.reason + '</td>';
          html += '</tr>';
        }
      });
      $('#hostTbody').html(html);
      $('#hostListArea').show();

      $('.publish-btn').off('click').on('click', function () {
        var hostId = $(this).data('hid');
        var $row   = $(this).closest('tr');
        var price  = $row.find('.price-input').val();

        if (!price || parseFloat(price) <= 0) {
          layer.msg('请输入有效的售价', {icon: 5});
          return;
        }

        doPublish(currentUid, hostId, price, $(this));
      });
    },
    error: function () {
      btn.prop('disabled', false).html('<i class="fas fa-search"></i> 查询');
      layer.msg('请求失败', {icon: 5});
    }
  });
}

function doPublish(uid, hostId, price, btn) {
  layer.confirm('确认将主机 #' + hostId + ' 以 ¥' + price + ' 直接上架吗？', {
    btn: ['确认上架', '取消']
  }, function () {
    btn.prop('disabled', true).text('处理中...');
    $.ajax({
      type: 'POST',
      url: '{:shd_addon_url("market://AdminIndex/doManualPublish")}',
      data: { uid: uid, host_id: hostId, sale_price: price },
      dataType: 'json',
      success: function (res) {
        if (res.status == 200) {
          layer.msg(res.msg, {icon: 1, time: 1500});
          setTimeout(function () { loadHosts(currentUid); }, 1500);
        } else {
          layer.msg(res.msg, {icon: 5});
          btn.prop('disabled', false).text('上架');
        }
      },
      error: function () {
        layer.msg('请求失败', {icon: 5});
        btn.prop('disabled', false).text('上架');
      }
    });
  });
}
</script>
