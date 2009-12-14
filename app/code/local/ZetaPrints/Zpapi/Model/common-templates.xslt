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
                  <select id="page-{$page}-field-{position()}" name="zetaprints-_{@FieldName}" title="{@Hint}">
                    <xsl:for-each select="Value">
                      <option><xsl:value-of select="." /></option>
                    </xsl:for-each>
                  </select>
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
        <dl class="zetaprints-stock-image-selector">
          <dt>
            <input class="stock-image" type="checkbox" id="page-{$page}-stock-image-{position()}" name="zetaprints-#{@Name}" disabled="true" />
            <label for="page-{$page}-stock-images-{position()}">
              <xsl:value-of select="@Name" />
              <xsl:text>:</xsl:text>
            </label>
          </dt>
          <dd>
            <div class="images-scroller">
              <ul>
                <xsl:if test="@ColourPicker='RGB'">
                  <li>
                    <div class="image">
                      <div class="color-sample"><span>Choose a color</span></div>
                    </div>
                  </li>
                </xsl:if>
                <xsl:for-each select="StockImage">
                  <li>
                    <img id="{@FileID}" class="image" src="{$zetaprints-api-url}photothumbs/{substring-before(@Thumb,'.')}_0x100.{substring-after(@Thumb,'.')}" />
                  </li>
                </xsl:for-each>
              </ul>
            </div>
          </dd>
        </dl>
    </xsl:for-each>
  </xsl:template>

  <xsl:template name="image-tabs-for-pages">
    <div class="image-tabs">
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
