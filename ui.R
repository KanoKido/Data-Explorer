library(shiny)

#规定数据文件大小上限
options(shiny.maxRequestSize=30*1024^2)

shinyUI(fluidPage(
  
  titlePanel("Data Explorer"),
  
  sidebarLayout(
    
    sidebarPanel(
      fileInput('data_file', 'Choose CSV File',
                accept=c('text/csv', 'text/comma-separated-values',
                         'text/plain', '.csv')),
      
      #表单折叠按钮
      HTML("<button type='button' class='btn btn-danger' data-toggle='collapse' data-target='#options'>File Loading Options</button>"),
      
      #表单的可折叠部分
      div(id = "options", class = "collapse", #默认显示：class="collapse in"
          checkboxInput('header', 'Header', TRUE),
          radioButtons('sep', 'Separator',
                       c(Comma=',', Semicolon=';', 
                         Space=' ', Tab='t'), ','),
          radioButtons('quote', 'Quote',
                       c(None='',
                         'Double Quote'='"',
                         'Single Quote'="'"),
                       '"')
      ),
      
      tags$hr(),
      
      #选择X轴和Y轴变量
      selectInput("x_axis", 
                  label = "X axis variable",
                  choices = "Please Select One",
                  selected = "Please Select One"),
      selectInput("y_axis", 
                  label = "Y axis variable",
                  choices = "Please Select One",
                  selected = "Please Select One"),
      #选择是否画出回归线
      checkboxInput('reg_line', 'Regression Line', TRUE)
    ),
    
    mainPanel(
      #用三个标签的形式显示结果
      tabsetPanel( 
        tabPanel("Raw Data", tableOutput('data_table')), 
        tabPanel("Scatter Plot", plotOutput("scatter_plot")),
        tabPanel("Model Summary", verbatimTextOutput("model_summary"))
      )
    )
    
  )
))

