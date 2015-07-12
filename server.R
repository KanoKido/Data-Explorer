library(shiny)

shinyServer(function(input, output, session) {
  
  #读取数据
  readData <- reactive({
    inFile <- input$data_file
    if (is.null(inFile))
      return(NULL)
    read.csv(inFile$datapath, header=input$header, sep=input$sep, 
             quote=input$quote)
  })
  
  #获取变量列表
  getVarList <- reactive({
    data = readData()
    names(data)
  })
  
  #动态更新变量下拉菜单
  observe({
    updateSelectInput(session, "x_axis",
                      choices = c("Please Select One", getVarList()),
                      selected = "Please Select One")
    updateSelectInput(session, "y_axis",
                      choices = c("Please Select One", getVarList()),
                      selected = "Please Select One")
  })
  
  #输出原始数据表
  output$data_table <- renderTable({
    readData()
  })
  
  #原样输出模型summary信息
  output$model_summary <- renderPrint({
    data <- readData()
    if(is.null(data)) return(NULL)
    if(input$x_axis!="Please Select One" &&
         input$y_axis!="Please Select One") {
      x=data[,input$x_axis]
      y=data[,input$y_axis]
      lm.sol = lm(y~1+x, data=data)
      summary(lm.sol)
    }
  })
  
  #绘制散点图和回归线
  output$scatter_plot <- renderPlot({
    data <- readData()
    if(is.null(data)) return(NULL)
    if(input$x_axis!="Please Select One" &&
         input$y_axis!="Please Select One") {
      x=data[,input$x_axis]
      y=data[,input$y_axis]
      plot(x,y,col = "black")
      if(input$reg_line){
        lm.sol = lm(y~x,data = data)
        abline(lm.sol,col = "red")
      }
    }
  })
  
})
