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
                <input type="text" class="form-control" id="keywordInput" placeholder="搜索产品">
                <btn class="btn btn-primary w-xs" id="searchBtn"><i class="fas fa-search"></i> 搜索</btn>
              </div>
            </div>

            <div class="table-body table-responsive">
              <table class="table table-bordered table-hover">
                <thead class="thead-light">
                  <tr>
                    <th class="center">ID</th>
                    <th>卖家</th>
                    <th>售价</th>
                    <th>产品</th>
                    <th>配置</th>
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

<div class="modal fade" id="specModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">编辑配置信息</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="specListingId" value="0">
        <div id="specFormFields"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
        <button type="button" class="btn btn-primary" id="saveSpecBtn">保存</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.bootcdn.net/ajax/libs/layer/3.5.1/layer.js"></script>
<script type="text/javascript">
var currentPage = 1;
var currentLimit = 20;
var listingCache = {};

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
        listingCache[v.id] = { spec_data: v.spec_data, notes: v.notes || '' };

        var featuredIcon = v.is_featured == '1'
          ? '<i class="fa fa-star text-warning"></i>'
          : '<i class="fa fa-star text-muted"></i>';
        var featuredBtn = v.is_featured == '1'
          ? '<a class="btn btn-link text-warning" onclick="toggleFeature(' + v.id + ')">取消推荐</a>'
          : '<a class="btn btn-link" onclick="toggleFeature(' + v.id + ')">推荐</a>';

        var notesHtml = '';
        if (v.notes) {
          notesHtml = '<span class="notes-badge" title="点击编辑备注" onclick="editNotes(' + v.id + ')" style="cursor:pointer;color:#f0ad4e;"><i class="fas fa-sticky-note"></i></span> ';
        } else {
          notesHtml = '<span class="notes-badge text-muted" title="添加备注" onclick="editNotes(' + v.id + ')" style="cursor:pointer;"><i class="far fa-sticky-note"></i></span> ';
        }

        var actions = notesHtml;
        if (v.status == '0') {
          actions += '<a class="btn btn-link green" onclick="doAudit(' + v.id + ',\'pass\')">通过</a>';
          actions += '<a class="btn btn-link red" onclick="doAudit(' + v.id + ',\'reject\')">驳回</a>';
        }
        if (v.status == '0' || v.status == '1') {
          actions += '<a class="btn btn-link" onclick="editSpec(' + v.id + ')">编辑配置</a>';
        }
        if (v.status != '2') {
          actions += '<a class="btn btn-link red" onclick="doDelete(' + v.id + ')">删除</a>';
        }

        var statusBadge = '';
        if (v.status == '0') statusBadge = '<span class="badge badge-warning">' + (v.status_text || '待审核') + '</span>';
        else if (v.status == '1') statusBadge = '<span class="badge badge-success">' + (v.status_text || '上架中') + '</span>';
        else if (v.status == '2') statusBadge = '<span class="badge badge-info">' + (v.status_text || '已售出') + '</span>';
        else if (v.status == '3') statusBadge = '<span class="badge badge-secondary">' + (v.status_text || '已下架') + '</span>';

        var specText = '';
        if (v.spec_data && typeof v.spec_data == 'object') {
          var parts = [];
          $.each(v.spec_data, function (key, val) {
            if (Array.isArray(val)) {
              parts.push(key + ': ' + val.join(', '));
            } else {
              parts.push(key + ': ' + val);
            }
          });
          specText = parts.join('<br>');
        }

        html += '<tr>';
        html += '<td class="center">' + v.id + '</td>';
        html += '<td>' + (v.seller || '') + '</td>';
        html += '<td>' + v.sale_price + '</td>';
        html += '<td>' + (v.product_name || '') + '</td>';
        html += '<td style="font-size:12px;">' + specText + '</td>';
        html += '<td>' + (v.nextduedate ? new Date(v.nextduedate * 1000).toLocaleDateString() : '') + '</td>';
        html += '<td class="center">' + featuredIcon + ' ' + featuredBtn + '</td>';
        html += '<td class="center">' + statusBadge + '</td>';
        html += '<td style="white-space:nowrap;">' + actions + '</td>';
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
  layer.confirm('确定要删除这个商品吗？删除后产品将从中间账户退还给原卖家。', {
    btn: ['确定删除', '取消']
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

var specFieldsCache = null;

function loadSpecFields(callback) {
  if (specFieldsCache) {
    callback(specFieldsCache);
    return;
  }
  $.ajax({
    type: 'GET',
    url: '{:shd_addon_url("market://AdminIndex/getFields")}',
    dataType: 'json',
    success: function (res) {
      if (res.status == 200) {
        specFieldsCache = res.data;
        callback(specFieldsCache);
      } else {
        layer.msg('加载配置字段失败', {icon: 5});
      }
    }
  });
}

function editSpec(id) {
  loadSpecFields(function (fields) {
    var listing = listingCache[id] || { spec_data: {} };

    var html = '';
    if (!fields || fields.length === 0) {
      html = '<p class="text-muted text-center">暂未配置自定义字段，请先在系统配置中添加</p>';
    } else {
      $.each(fields, function (i, f) {
        var val = (listing.spec_data && listing.spec_data[f.field_name]) ? listing.spec_data[f.field_name] : '';
        html += '<div class="form-group">';
        html += '<label>' + f.field_label + ' <small class="text-muted">(' + f.field_name + ')</small>';
        if (f.is_required == 1) html += ' <span class="text-danger">*</span>';
        html += '</label>';
        if (f.field_type == 'input') {
          html += '<input type="text" class="form-control spec-field" data-name="' + f.field_name + '" value="' + (typeof val === 'string' ? val.replace(/"/g, '&quot;') : val) + '">';
        } else if (f.field_type == 'dropdown') {
          html += '<select class="form-control spec-field" data-name="' + f.field_name + '">';
          html += '<option value="">-- 请选择 --</option>';
          if (f.field_options) {
            $.each(f.field_options, function (j, opt) {
              html += '<option value="' + opt + '" ' + (val == opt ? 'selected' : '') + '>' + opt + '</option>';
            });
          }
          html += '</select>';
        } else if (f.field_type == 'radio') {
          if (f.field_options) {
            $.each(f.field_options, function (j, opt) {
              html += '<div class="form-check form-check-inline">';
              html += '<input class="form-check-input spec-field" type="radio" name="spec_' + f.field_name + '" data-name="' + f.field_name + '" value="' + opt + '" ' + (val == opt ? 'checked' : '') + '>';
              html += '<label class="form-check-label">' + opt + '</label>';
              html += '</div>';
            });
          }
        } else if (f.field_type == 'checkbox') {
          var selected = Array.isArray(val) ? val : (val ? [val] : []);
          if (f.field_options) {
            $.each(f.field_options, function (j, opt) {
              html += '<div class="form-check form-check-inline">';
              html += '<input class="form-check-input spec-checkbox" data-name="' + f.field_name + '" value="' + opt + '" ' + (selected.indexOf(opt) >= 0 ? 'checked' : '') + '>';
              html += '<label class="form-check-label">' + opt + '</label>';
              html += '</div>';
            });
          }
        }
        html += '</div>';
      });
    }

    $('#specListingId').val(id);
    $('#specFormFields').html(html);
    $('#specModal').modal('show');
  });
}

$('#saveSpecBtn').on('click', function () {
  var id = $('#specListingId').val();
  var specData = {};

  $('.spec-field').each(function () {
    var name = $(this).data('name');
    var isRadio = $(this).attr('type') == 'radio';
    if (isRadio) {
      if ($(this).is(':checked')) {
        specData[name] = $(this).val();
      }
    } else {
      specData[name] = $(this).val();
    }
  });

  $('.spec-checkbox').each(function () {
    var name = $(this).data('name');
    if (!specData[name]) specData[name] = [];
    if ($(this).is(':checked')) {
      specData[name].push($(this).val());
    }
  });

  if (specFieldsCache) {
    for (var i = 0; i < specFieldsCache.length; i++) {
      var f = specFieldsCache[i];
      if (f.is_required != 1) continue;
      var val = specData[f.field_name];
      var isEmpty = (val === undefined || val === null || val === '');
      if (Array.isArray(val) && val.length === 0) isEmpty = true;
      if (isEmpty) {
        layer.msg('请填写必填项：' + f.field_label, {icon: 5});
        return;
      }
    }
  }

  var btn = $(this);
  btn.prop('disabled', true).text('保存中...');
  $.ajax({
    type: 'POST',
    url: '{:shd_addon_url("market://AdminIndex/updateSpec")}',
    data: { id: id, spec_data: JSON.stringify(specData) },
    dataType: 'json',
    success: function (res) {
      if (res.status == 200) {
        layer.msg(res.msg, {icon: 1, time: 1500});
        $('#specModal').modal('hide');
        setTimeout(function () { loadList(currentPage); }, 1500);
      } else {
        layer.msg(res.msg, {icon: 5});
      }
      btn.prop('disabled', false).text('保存');
    },
    error: function () {
      layer.msg('保存失败', {icon: 5});
      btn.prop('disabled', false).text('保存');
    }
  });
});

function editNotes(id) {
  var currentNotes = (listingCache[id] && listingCache[id].notes) ? listingCache[id].notes : '';

  layer.prompt({
    title: '编辑卖家备注',
    formType: 2,
    value: currentNotes,
    area: ['400px', '200px']
  }, function (notes, index) {
    layer.close(index);
    $.ajax({
      type: 'POST',
      url: '{:shd_addon_url("market://AdminIndex/updateNotes")}',
      data: { id: id, notes: notes },
      dataType: 'json',
      success: function (res) {
        if (res.status == 200) {
          listingCache[id].notes = notes;
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
