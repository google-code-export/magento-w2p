<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:template name="fields-for-page">
    <xsl:param name="page" />

    <xsl:for-each select="//Fields/Field[@Page=$page]">
      <dl>
        <dt>
          <label for="page-{$page}-field-{position()}">
            <xsl:value-of select="@FieldName" />
            <xsl:text>:</xsl:text>
          </label>
        </dt>
        <dd>
          <xsl:choose>
            <xsl:when test="@Multiline">
              <textarea id="page-{$page}-field-{position()}" name="zetaprints-_{@FieldName}">
                <xsl:if test="string-length(@Hint)!=0">
                  <xsl:attribute name="title"><xsl:value-of select="@Hint" /></xsl:attribute>
                </xsl:if>
                <xsl:text>&#x0A;</xsl:text>
              </textarea>
            </xsl:when>
            <xsl:otherwise>
              <xsl:choose>
                <xsl:when test="count(Value)=0">
                  <input type="text" id="page-{$page}-field-{position()}" name="zetaprints-_{@FieldName}" class="input-text">
                    <xsl:if test="@MaxLen">
                      <xsl:attribute name="maxlength"><xsl:value-of select="@MaxLen" /></xsl:attribute>
                    </xsl:if>
                    <xsl:if test="string-length(@Hint)!=0">
                      <xsl:attribute name="title"><xsl:value-of select="@Hint" /></xsl:attribute>
                    </xsl:if>
                  </input>
                </xsl:when>
                <xsl:otherwise>
                  <xsl:choose>
                    <xsl:when test="count(Value)=2 and string-length(Value[last()])=0">
                      <input type="hidden" name="zetaprints-_{@FieldName}" value="&#x2E0F;" />
                      <input id="page-{$page}-field-{position()}" type="checkbox" name="zetaprints-_{@FieldName}" value="{Value[1]}" title="{@Hint}" />
                    </xsl:when>
                    <xsl:otherwise>
                      <select id="page-{$page}-field-{position()}" name="zetaprints-_{@FieldName}" title="{@Hint}">
                        <xsl:for-each select="Value">
                          <option><xsl:value-of select="." /></option>
                        </xsl:for-each>
                      </select>
                    </xsl:otherwise>
                  </xsl:choose>
                </xsl:otherwise>
              </xsl:choose>
            </xsl:otherwise>
          </xsl:choose>
        </dd>
      </dl>
    </xsl:for-each>
  </xsl:template>

  <xsl:template name="stock-images-for-page">
    <xsl:param name="page" />

    <xsl:for-each select="//Images/Image[@Page=$page]">
      <div class="zetaprints-images-selector no-value minimized base-mini">
        <div class="head">
          <div class="icon"><span /></div>
          <div class="title">
            <label><xsl:value-of select="@Name" /></label>
          </div>
          <a class="image up-down" href="#"><span>Up/Down</span></a>
          <a class="image collapse-expand" href="#"><span>Collapse/Expand</span></a>
        </div>
        <div id="page-{$page}-tabs-{position()}" class="selector-content">
          <ul class="tab-buttons">
            <xsl:if test="@AllowUpload='1'">
              <li>
                <div class="icon upload"><span /></div>
                <a href="#page-{$page}-tabs-{position()}-1"><span>Upload</span></a>
              </li>
              <li>
                <div class="icon user-images"><span /></div>
                <a href="#page-{$page}-tabs-{position()}-2"><span>My images</span></a>
              </li>
            </xsl:if>
            <xsl:if test="StockImage">
              <li>
                <div class="icon stock-images"><span /></div>
                <a href="#page-{$page}-tabs-{position()}-3"><span>Stock images</span></a>
              </li>
            </xsl:if>
            <xsl:if test="@ColourPicker='RGB'">
              <li>
                <div class="icon color-picker"><span /></div>
                <a href="#page-{$page}-tabs-{position()}-4"><span>Color picker</span></a>
              </li>
            </xsl:if>
            <li class="last"><label><input type="radio" name="zetaprints-#{@Name}" value="" /> Leave blank</label></li>
          </ul>
          <div class="tabs-wrapper">
          <xsl:if test="@AllowUpload='1'">
            <div id="page-{$page}-tabs-{position()}-1" class="tab upload">
              <div class="column">
                <input type="text" class="input-text file-name" disabled="true" />
                <label>Upload new image from your computer</label>
              </div>

              <div class="column">
                <div class="button choose-file"><span /></div>
                <div class="button upload-file disabled"><span /></div>
                <img class="ajax-loader" src="{$ajax-loader-image-url}" />
              </div>

              <div class="clear"><span /></div>
            </div>
            <div id="page-{$page}-tabs-{position()}-2" class="tab user-images images-scroller">
              <input type="hidden" name="parameter" value="zetaprints-#{@Name}" />
              <ul>
                <replace-with-user-images name="zetaprints-#{@Name}" />
              </ul>
            </div>
          </xsl:if>
          <xsl:if test="StockImage">
            <div id="page-{$page}-tabs-{position()}-3" class="tab images-scroller">
              <ul>
                <xsl:for-each select="StockImage">
                  <li>
                    <input type="radio" name="zetaprints-#{../@Name}" value="{@FileID}" /><br />
                    <img src="{$zetaprints-api-url}photothumbs/{substring-before(@Thumb,'.')}_0x100.{substring-after(@Thumb,'.')}" />
                  </li>
                </xsl:for-each>
              </ul>
            </div>
          </xsl:if>

          <xsl:if test="@ColourPicker='RGB'">
            <div id="page-{$page}-tabs-{position()}-4" class="tab color-picker">
              <input type="radio" name="zetaprints-#{@Name}" disabled="1" value="" />
              <div class="color-sample"><span /></div>
              <span><a href="#">Choose a color</a> and click Select to fill the place of the photo.</span>
            </div>
          </xsl:if>
          </div>

          <div class="clear"><span /></div>
        </div>
      </div>
    </xsl:for-each>
  </xsl:template>

  <xsl:template name="image-tabs-for-pages">
    <div class="zetaprints-image-tabs">
      <ul style="width: {count(Page) * 135}px;">
      <xsl:for-each select="Page">
          <li title="Click to show page">
            <img rel="page-{position()}" src="{$zetaprints-api-url}{substring-before(@ThumbImage, '.')}_100x100.{substring-after(@ThumbImage, '.')}" />
            <br />
            <span><xsl:value-of select="@Name" /></span>
          </li>
        </xsl:for-each>
      </ul>
    </div>
  </xsl:template>
</xsl:stylesheet>
