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
            <div class="col text-right" style="padding-right:15px;">
              <span style="color:#999;font-size:13px;font-style:italic;">君知所向，故无所惧。</span>
            </div>
          </div>
          <div class="help-block">
            配置RuiNexus Market二手服务器交易市场的各项参数
          </div>

          <form method="post" action="{:shd_addon_url('market://AdminIndex/configPost')}" class="needs-validation" novalidate>
            <div class="px-5 mx-auto w-75">
              <div class="row">
                <div class="col-sm-6 col-12">
                  <div class="form-group">
                    <label>站点名称</label>
                    <input type="text" class="form-control" name="site_name" value="{$config['site_name'] ?? 'RuiNexus Market'}">
                  </div>
                </div>
                <div class="col-sm-6 col-12">
                  <div class="form-group">
                    <label>手续费比例(%)</label>
                    <input type="number" class="form-control" name="fee_percent" value="{$config['fee_percent'] ?? '5'}" min="0" max="100">
                  </div>
                </div>
                <div class="col-sm-6 col-12">
                  <div class="form-group">
                    <label>联系邮箱</label>
                    <input type="email" class="form-control" name="contact_email" value="{$config['contact_email'] ?? ''}" placeholder="admin@example.com">
                  </div>
                </div>
                <div class="col-sm-6 col-12">
                  <div class="form-group">
                    <label>联系QQ</label>
                    <input type="text" class="form-control" name="contact_qq" value="{$config['contact_qq'] ?? ''}" placeholder="123456789">
                  </div>
                </div>
                <div class="col-sm-6 col-12">
                  <div class="form-group">
                    <label>上架审核</label>
                    <select class="form-control" name="need_audit">
                      <option value="1" {if ($config['need_audit'] ?? '1') == '1'}selected{/if}>需要审核</option>
                      <option value="0" {if ($config['need_audit'] ?? '') == '0'}selected{/if}>直接上架</option>
                    </select>
                  </div>
                </div>
                <div class="col-sm-6 col-12">
                  <div class="form-group">
                    <label>线下交易</label>
                    <select class="form-control" name="allow_offline">
                      <option value="1" {if ($config['allow_offline'] ?? '1') == '1'}selected{/if}>允许</option>
                      <option value="0" {if ($config['allow_offline'] ?? '') == '0'}selected{/if}>禁止</option>
                    </select>
                  </div>
                </div>
                <div class="col-sm-6 col-12">
                  <div class="form-group">
                    <label>中间账户UID</label>
                    <input type="number" class="form-control" name="escrow_uid" value="{$config['escrow_uid'] ?? '0'}" min="0" placeholder="0=不启用">
                    <small class="form-text text-muted">上架后自动转移产品到此账户，交易完成转移给买家，下架后退回卖家</small>
                  </div>
                </div>
                <div class="col-12">
                  <div class="form-group">
                    <label>禁止交易的产品ID</label>
                    <input type="text" class="form-control" name="product_blacklist" value="{$config['product_blacklist'] ?? ''}" placeholder="逗号分隔，如: 1,2,3">
                  </div>
                </div>
                <div class="col-12">
                  <div class="form-group">
                    <label>公告内容</label>
                    <textarea rows="4" class="form-control" name="notice_content" placeholder="前端页面公告区域显示的内容">{$config['notice_content'] ?? ''}</textarea>
                  </div>
                </div>
                <div class="col-sm-12">
                  <div class="form-group mb-0">
                    <button type="submit" class="btn btn-primary w-xl submitBtn">保存配置</button>
                  </div>
                </div>
              </div>
            </div>
          </form>

          <hr class="my-4">

          <div class="card-title">
            <h5 class="m-0">自定义配置字段</h5>
            <small class="text-muted">卖家在上架时可以自定义填写这些配置信息，如CPU、内存、带宽等</small>
          </div>

          <div>
            <div class="mb-2">
              <button class="btn btn-primary w-xs" id="addFieldBtn"><i class="fas fa-plus"></i> 新增字段</button>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-hover">
                <thead class="thead-light">
                  <tr>
                    <th class="center" style="width:60px;">ID</th>
                    <th style="width:120px;">标识</th>
                    <th>显示名</th>
                    <th class="center" style="width:100px;">类型</th>
                    <th style="width:200px;">选项</th>
                    <th class="center" style="width:60px;">排序</th>
                    <th class="center" style="width:60px;">必填</th>
                    <th class="center" style="width:120px;">操作</th>
                  </tr>
                </thead>
                <tbody id="fieldTbody">
                  {foreach $fields as $f}
                  <tr>
                    <td class="center">{$f.id}</td>
                    <td><code>{$f.field_name}</code></td>
                    <td>{$f.field_label}</td>
                    <td class="center">
                      {if $f.field_type == 'input'}文本框
                      {elseif $f.field_type == 'number'}数字
                      {elseif $f.field_type == 'dropdown'}下拉
                      {elseif $f.field_type == 'radio'}单选
                      {elseif $f.field_type == 'checkbox'}多选
                      {else}{$f.field_type}{/if}
                    </td>
                    <td class="text-muted" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                      {if $f.field_options}{$f.field_options}{else}--{/if}
                    </td>
                    <td class="center">{$f.sort_order}</td>
                    <td class="center">{if $f.is_required}是{else}否{/if}</td>
                    <td class="center">
                      <a class="btn btn-link edit-field" data-id="{$f.id}" data-name="{$f.field_name}" data-label="{$f.field_label}" data-type="{$f.field_type}" data-options="{$f.field_options|htmlspecialchars}" data-order="{$f.sort_order}" data-required="{$f.is_required}">编辑</a>
                      <a class="btn btn-link red del-field" data-id="{$f.id}">删除</a>
                    </td>
                  </tr>
                  {/foreach}
                  {if empty($fields)}
                  <tr><td colspan="8" class="text-center text-muted">暂无自定义字段，点击"新增字段"添加</td></tr>
                  {/if}
                </tbody>
              </table>
            </div>
          </div>

          <hr class="my-4">

          <div class="card-title">
            <h5 class="m-0">
              JWT 认证校验测试
              <span class="badge badge-warning ml-2" style="font-size:12px;vertical-align:middle;">🧪 开发测试功能</span>
            </h5>
            <small class="text-muted">粘贴浏览器 Cookie 中的 JWT Token，逐层验证认证链路是否正常。仅用于开发调试，生产环境可移除。</small>
          </div>

          <div class="px-5 mx-auto w-75">
            <div class="form-group">
              <label>JWT Token <small class="text-muted">(从浏览器 Cookie 或 Authorization Header 中获取)</small></label>
              <div class="input-group">
                <textarea class="form-control" id="jwtTokenInput" rows="3" placeholder="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."></textarea>
                <div class="input-group-append">
                  <button class="btn btn-info" type="button" id="jwtCheckBtn" style="height:auto;">
                    <i class="fas fa-check-circle"></i> 校验 JWT
                  </button>
                </div>
              </div>
            </div>
            <div id="jwtResult" style="display:none;">
              <div id="jwtOverall" class="alert mb-2" style="padding:6px 12px;"></div>
              <div id="jwtSteps" class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                  <thead class="thead-light">
                    <tr><th style="width:40px;">#</th><th>校验项</th><th style="width:80px;">结果</th><th>详情</th></tr>
                  </thead>
                  <tbody id="jwtStepsBody"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="fieldModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="fieldModalTitle">新增字段</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="editFieldId" value="0">
        <div class="form-group">
          <label>字段标识 <small class="text-muted">(小写字母开头，如 cpu、ram、bandwidth)</small></label>
          <input type="text" class="form-control" id="fieldName" placeholder="cpu">
        </div>
        <div class="form-group">
          <label>显示名</label>
          <input type="text" class="form-control" id="fieldLabel" placeholder="CPU 核心">
        </div>
        <div class="form-group">
          <label>字段类型</label>
          <select class="form-control" id="fieldType">
            <option value="input">文本框 (input)</option>
            <option value="number">数字 (number)</option>
            <option value="dropdown">下拉选择 (dropdown)</option>
            <option value="radio">单选 (radio)</option>
            <option value="checkbox">多选 (checkbox)</option>
          </select>
        </div>
        <div class="form-group" id="optionsGroup" style="display:none;">
          <label>选项 <small class="text-muted">(每行一个)</small></label>
          <textarea class="form-control" id="fieldOptions" rows="4" placeholder="16核&#10;32核&#10;64核"></textarea>
        </div>
        <div class="form-group">
          <label>排序</label>
          <input type="number" class="form-control" id="fieldOrder" value="0" min="0">
        </div>
        <div class="form-group">
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="fieldRequired">
            <label class="form-check-label" for="fieldRequired">必填</label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
        <button type="button" class="btn btn-primary" id="saveFieldBtn">保存</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.bootcdn.net/ajax/libs/layer/3.5.1/layer.js"></script>
<script type="text/javascript">
$(function () {
  $('form').on('submit', function (e) {
    e.preventDefault();
    var btn = $(this).find('.submitBtn');
    btn.prop('disabled', true).text('保存中...');
    $.ajax({
      type: 'POST',
      url: $(this).attr('action'),
      data: $(this).serialize(),
      dataType: 'json',
      success: function (res) {
        if (res.status == 200) {
          layer.msg(res.msg, {icon: 1, time: 2000});
        } else {
          layer.msg(res.msg, {icon: 5});
        }
        btn.prop('disabled', false).text('保存配置');
      },
      error: function () {
        layer.msg('保存失败', {icon: 5});
        btn.prop('disabled', false).text('保存配置');
      }
    });
  });

  $('#fieldType').on('change', function () {
    var type = $(this).val();
    if (type == 'dropdown' || type == 'radio' || type == 'checkbox') {
      $('#optionsGroup').show();
    } else {
      $('#optionsGroup').hide();
    }
  });

  $('#addFieldBtn').on('click', function () {
    $('#editFieldId').val(0);
    $('#fieldModalTitle').text('新增字段');
    $('#fieldName').val('').prop('readonly', false);
    $('#fieldLabel').val('');
    $('#fieldType').val('input');
    $('#fieldOptions').val('');
    $('#fieldOrder').val(0);
    $('#fieldRequired').prop('checked', false);
    $('#optionsGroup').hide();
    $('#fieldModal').modal('show');
  });

  $(document).on('click', '.edit-field', function () {
    var $this = $(this);
    $('#editFieldId').val($this.data('id'));
    $('#fieldModalTitle').text('编辑字段');
    $('#fieldName').val($this.data('name')).prop('readonly', true);
    $('#fieldLabel').val($this.data('label'));
    $('#fieldType').val($this.data('type')).trigger('change');
    var opts = $this.data('options');
    if (opts) {
      try {
        var arr = JSON.parse(opts);
        $('#fieldOptions').val(arr.join('\n'));
      } catch(e) {
        $('#fieldOptions').val(opts);
      }
    } else {
      $('#fieldOptions').val('');
    }
    $('#fieldOrder').val($this.data('order'));
    $('#fieldRequired').prop('checked', parseInt($this.data('required')) == 1);
    $('#fieldModal').modal('show');
  });

  $('#saveFieldBtn').on('click', function () {
    var id = $('#editFieldId').val();
    var name = $('#fieldName').val().trim();
    var label = $('#fieldLabel').val().trim();
    var type = $('#fieldType').val();
    var options = $('#fieldOptions').val();
    var order = $('#fieldOrder').val();
    var required = $('#fieldRequired').is(':checked') ? 1 : 0;

    if (!name) { layer.msg('请输入字段标识', {icon: 5}); return; }
    if (!label) { layer.msg('请输入显示名', {icon: 5}); return; }

    var btn = $(this);
    btn.prop('disabled', true).text('保存中...');
    $.ajax({
      type: 'POST',
      url: '{:shd_addon_url("market://AdminIndex/saveField")}',
      data: { id: id, field_name: name, field_label: label, field_type: type, field_options: options, sort_order: order, is_required: required },
      dataType: 'json',
      success: function (res) {
        if (res.status == 200) {
          layer.msg(res.msg, {icon: 1, time: 1500});
          $('#fieldModal').modal('hide');
          setTimeout(function () { location.reload(); }, 1500);
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

  $(document).on('click', '.del-field', function () {
    var id = $(this).data('id');
    layer.confirm('确定要删除这个字段吗？<br><small class="text-muted">已上架商品的 spec_data 不受影响</small>', {
      btn: ['确定删除', '取消']
    }, function () {
      $.ajax({
        type: 'POST',
        url: '{:shd_addon_url("market://AdminIndex/deleteField")}',
        data: { id: id },
        dataType: 'json',
        success: function (res) {
          if (res.status == 200) {
            layer.msg(res.msg, {icon: 1, time: 1500});
            setTimeout(function () { location.reload(); }, 1500);
          } else {
            layer.msg(res.msg, {icon: 5});
          }
        }
      });
    });
  });

  $('#jwtCheckBtn').on('click', function () {
    var token = $('#jwtTokenInput').val().trim();
    if (!token) { layer.msg('请粘贴 JWT Token', {icon: 5}); return; }

    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 校验中...');

    $.ajax({
      type: 'POST',
      url: '{:shd_addon_url("market://AdminIndex/jwtCheck")}',
      data: { token: token },
      dataType: 'json',
      success: function (res) {
        btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> 校验 JWT');
        if (res.status == 200) {
          var data = res.data;
          $('#jwtResult').show();

          if (data.valid) {
            $('#jwtOverall').removeClass('alert-danger').addClass('alert-success')
              .html('<strong><i class="fas fa-check-circle"></i> 全部校验通过！</strong> 用户：' + data.user.username + ' (' + data.user.email + ')，uid=' + data.uid);
          } else {
            $('#jwtOverall').removeClass('alert-success').addClass('alert-danger')
              .html('<strong><i class="fas fa-times-circle"></i> 校验未通过</strong>，请查看下方详情定位问题');
          }

          var rows = '';
          $.each(data.steps, function (i, step) {
            var icon = step.pass ? '<span class="text-success font-weight-bold">✅ 通过</span>' : '<span class="text-danger font-weight-bold">❌ 失败</span>';
            rows += '<tr><td class="center">' + step.step + '</td><td>' + step.name + '</td><td class="center">' + icon + '</td><td>' + step.msg + '</td></tr>';
          });
          $('#jwtStepsBody').html(rows);
        } else {
          $('#jwtResult').show();
          $('#jwtOverall').removeClass('alert-success').addClass('alert-danger')
            .html('<strong><i class="fas fa-exclamation-triangle"></i></strong> ' + (res.msg || '校验失败'));
          $('#jwtStepsBody').html('<tr><td colspan="4" class="text-center text-muted">--</td></tr>');
        }
      },
      error: function () {
        btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> 校验 JWT');
        layer.msg('校验请求失败', {icon: 5});
      }
    });
  });
});
</script>
