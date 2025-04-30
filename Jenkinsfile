pipeline {
    agent any

    environment {
        RECIPIENTS = 'ghadaderouiche8@gmail.com'
        DOCKER_USERNAME = 'ghada522'
        DOCKER_PASSWORD = 'Ghoughou*2001'
    }

    stages {
        stage('Clone the code') {
            steps {
                git url: 'https://github.com/ghada634/peplinesec.git'
            }
        }

        stage('Run tests') {
            steps {
                bat '.\\vendor\\bin\\phpunit tests'
            }
        }

        stage('SonarQube analysis') {
            steps {
                script {
                    try {
                        withSonarQubeEnv('SonarQubeServer') {
                            bat 'sonar-scanner -Dsonar.projectKey=testprojet -Dsonar.sources=. -Dsonar.php.tests.reportPath=tests'
                        }
                    } catch (Exception e) {
                        echo "Error during SonarQube analysis: ${e.getMessage()}"
                        currentBuild.result = 'FAILURE'
                        throw e
                    }
                }
            }
        }

        stage('Build and Run Docker Compose') {
            steps {
                script {
                    try {
                        bat 'docker-compose -f docker-compose.yml up -d --build'
                    } catch (Exception e) {
                        echo "Error during Docker Compose launch: ${e.getMessage()}"
                        currentBuild.result = 'FAILURE'
                        throw e
                    }
                }
            }
        }

        stage('Trivy scan for Docker vulnerabilities') {
            steps {
                script {
                    try {
                        bat 'trivy image edoc-app'
                    } catch (Exception e) {
                        echo "Error during Trivy scan: ${e.getMessage()}"
                        currentBuild.result = 'FAILURE'
                        throw e
                    }
                }
            }
        }

        stage('Push Docker image to Docker Hub') {
            steps {
                script {
                    try {
                        bat "docker login -u ${DOCKER_USERNAME} -p ${DOCKER_PASSWORD}"
                        bat "docker tag edoc-app ${DOCKER_USERNAME}/edoc-app:latest"
                        bat "docker push ${DOCKER_USERNAME}/edoc-app:latest"
                    } catch (Exception e) {
                        echo "Error during Docker image push: ${e.getMessage()}"
                        currentBuild.result = 'FAILURE'
                        throw e
                    }
                }
            }
        }

        stage('Deploy on AWS') {
            steps {
                withCredentials([sshUserPrivateKey(credentialsId: 'ghada-key', keyFileVariable: 'SSH_KEY_FILE')]) {
                    script {
                        bat 'icacls %SSH_KEY_FILE% /inheritance:r'
                        bat 'icacls %SSH_KEY_FILE% /grant:r "test:F"'
                        bat '''
                            ssh -i %SSH_KEY_FILE% -o StrictHostKeyChecking=no ubuntu@3.84.219.170 ^
                            "cd ~/peplinesec && \
                            docker-compose down && \
                            git pull && \
                            docker-compose pull && \
                            docker-compose up -d"
                        '''
                    }
                }
            }
        }
    }

   post {
    success {
        mail(
            to: RECIPIENTS,
            subject: "SUCCESS - ${env.JOB_NAME} #${env.BUILD_NUMBER}",
            body: """
                <html>
                    <body>
                        <p>Hello Ghada,</p>
                        <p>The build <strong>succeeded</strong>.</p>
                        <p>Check the details here: <a href="${env.BUILD_URL}">${env.BUILD_URL}</a></p>
                    </body>
                </html>
            """,
            mimeType: 'text/html',
            charset: 'UTF-8'
        )
    }

    failure {
        mail(
            to: RECIPIENTS,
            subject: "FAILURE - ${env.JOB_NAME} #${env.BUILD_NUMBER}",
            body: """
                <html>
                    <body>
                        <p>Hello Ghada,</p>
                        <p>The build <strong>failed</strong>.</p>
                        <p>Check the logs here: <a href="${env.BUILD_URL}">${env.BUILD_URL}</a></p>
                    </body>
                </html>
            """,
            mimeType: 'text/html',
            charset: 'UTF-8'
        )
    }
}
