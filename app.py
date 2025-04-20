import sqlite3
import cv2
import os
from flask import Flask,request,render_template,redirect,session,url_for
from datetime import date
from datetime import datetime
import numpy as np
from sklearn.neighbors import KNeighborsClassifier
import pandas as pd
import joblib
import time
# import db

#VARIABLES
MESSAGE = "WELCOME  " \
          " Instruction: to register your attendence kindly click on 'a' on keyboard"

#### Defining Flask App
app = Flask(__name__)

#### Saving Date today in 2 different formats
datetoday = date.today().strftime("%m_%d_%y")
datetoday2 = date.today().strftime("%d-%B-%Y")

#### Initializing VideoCapture object to access WebCam
face_detector = cv2.CascadeClassifier('haarcascade_frontalface_default.xml')
try:
    cap = cv2.VideoCapture(1)
except:
    cap = cv2.VideoCapture(0)

#### If these directories don't exist, create them
if not os.path.isdir('Attendance'):
    os.makedirs('Attendance')
if not os.path.isdir('static'):
    os.makedirs('static')
if not os.path.isdir('static/faces'):
    os.makedirs('static/faces')
if f'Attendance-{datetoday}.csv' not in os.listdir('Attendance'):
    with open(f'Attendance/Attendance-{datetoday}.csv','w') as f:
        f.write('Name,Roll,Time')

#### get a number of total registered users

def totalreg():
    return len(os.listdir('static/faces'))

#### extract the face from an image
# def extract_faces(img):
#     if img!=[]:
#         gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
#         face_points = face_detector.detectMultiScale(gray, 1.3, 5)
#         return face_points
#     else:
#         return []

def extract_faces(img):
    if img is None or img.size == 0:  # Check if image is empty
        return []
    
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    face_points = face_detector.detectMultiScale(gray, 1.3, 5)
    
    if len(face_points) == 0:  # If no faces detected
        return []
    
    return face_points


#### Identify face using ML model
def identify_face(facearray):
    model = joblib.load('static/face_recognition_model.pkl')
    return model.predict(facearray)

#### A function which trains the model on all the faces available in faces folder
def train_model():
    faces = []
    labels = []
    userlist = os.listdir('static/faces')
    for user in userlist:
        for imgname in os.listdir(f'static/faces/{user}'):
            img = cv2.imread(f'static/faces/{user}/{imgname}')
            resized_face = cv2.resize(img, (50, 50))
            faces.append(resized_face.ravel())
            labels.append(user)
    faces = np.array(faces)
    knn = KNeighborsClassifier(n_neighbors=25)
    knn.fit(faces,labels)
    joblib.dump(knn,'static/face_recognition_model.pkl')

#### Extract info from today's attendance file in attendance folder
def extract_attendance():
    df = pd.read_csv(f'Attendance/Attendance-{datetoday}.csv')
    names = df['Name']
    rolls = df['Roll']
    times = df['Time']
    l = len(df)
    return names,rolls,times,l

#### Add Attendance of a specific user
def add_attendance(name):
    username = name.split('_')[0]
    userid = name.split('_')[1]
    current_time = datetime.now().strftime("%H:%M:%S")
    
    df = pd.read_csv(f'Attendance/Attendance-{datetoday}.csv')
    if str(userid) not in list(df['Roll']):
        with open(f'Attendance/Attendance-{datetoday}.csv','a') as f:
            f.write(f'\n{username},{userid},{current_time}')
    else:
        print("this user has already marked attendence for the day , but still i am marking it ")
        # with open(f'Attendance/Attendance-{datetoday}.csv','a') as f:
        #     f.write(f'\n{username},{userid},{current_time}')


################## ROUTING FUNCTIONS ##############################

#### Our main page
@app.route('/')
def home():
    names,rolls,times,l = extract_attendance()
    return render_template('index.html',names=names,rolls=rolls,times=times,l=l,totalreg=totalreg(),datetoday2=datetoday2, mess = MESSAGE)


#### This function will run when we click on Take Attendance Button
@app.route('/start',methods=['GET'])
def start():
    ATTENDENCE_MARKED = False
    if 'face_recognition_model.pkl' not in os.listdir('static'):
        names, rolls, times, l = extract_attendance()
        MESSAGE = 'This face is not registered with us , kindly register yourself first'
        print("face not in database, need to register")
        return render_template('home.html',names=names,rolls=rolls,times=times,l=l,totalreg=totalreg,datetoday2=datetoday2, mess = MESSAGE)
        # return render_template('home.html',totalreg=totalreg(),datetoday2=datetoday2,mess='There is no trained model in the static folder. Please add a new face to continue.')

    cap = cv2.VideoCapture(0)
    ret = True
    while True:
        # Read a frame from the camera
        ret, frame = cap.read()
        
        # Convert the frame to grayscale
        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        
        # Detect faces in the grayscale frame
        faces = face_detector.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5)
        
        # Draw rectangles around the detected faces
        for (x, y, w, h) in faces:
            cv2.rectangle(frame, (x, y), (x+w, y+h), (0, 255, 0), 2)
            face = cv2.resize(frame[y:y+h,x:x+w], (50, 50))
            identified_person = identify_face(face.reshape(1,-1))[0]
            cv2.putText(frame, f'{identified_person}', (x + 6, y - 6), cv2.FONT_HERSHEY_SIMPLEX, 1, (255, 0, 20), 2)
            if cv2.waitKey(1) == ord('a'):
                add_attendance(identified_person)
                current_time_ = datetime.now().strftime("%H:%M:%S")
                print(f"attendence marked for {identified_person}, at {current_time_} ")
                ATTENDENCE_MARKED = True
                break
            #i want to display matched user name in another html file 

            # matched_image_name = identified_person  # Store matched name in a variable
            # print(matched_image_name)
            return redirect(f"http://localhost/EMP_ATTENDANCE/result.php?matched_name={identified_person}")



        if ATTENDENCE_MARKED:
            # time.sleep(3)
            break

        # Display the resulting frame
        cv2.imshow('Attendance Check, press "q" to exit', frame)
        cv2.putText(frame,'hello',(30,30),cv2.FONT_HERSHEY_COMPLEX,2,(255, 255, 255))
        
    # Wait for the user to press 'q' to quit
        if cv2.waitKey(1) == ord('q'):
            break

    cap.release()
    cv2.destroyAllWindows()
    names, rolls, times, l = extract_attendance()
    MESSAGE = 'Attendence taken successfully'
    print("attendence registered")
    return render_template('home.html', names=names, rolls=rolls, times=times, l=l, totalreg=totalreg(),
                           datetoday2=datetoday2, mess=MESSAGE)

@app.route('/result')
def result():
    matched_name = request.args.get('matched_name', 'No Match Found')  # Get name from URL
    return render_template('result.html', matched_name=matched_name)  # Pass to HTML



from flask import redirect

@app.route('/add', methods=['GET'])
def add():
    email = request.args.get('email')
    if not email:
        return "Email parameter is required", 400

    userimagefolder = f'static/faces/{email}'
    
    if not os.path.isdir(userimagefolder):
        os.makedirs(userimagefolder)

    # Release any existing camera instances
    try:
        if 'cap' in globals():
            if cap is not None:
                cap.release()
    except:
        pass
        
    # Try to initialize camera with a retry mechanism
    cap = None
    max_attempts = 3
    attempts = 0
    
    while attempts < max_attempts and (cap is None or not cap.isOpened()):
        try:
            cap = cv2.VideoCapture(0)
            # Wait a moment to initialize
            time.sleep(1)
            attempts += 1
            
            # Test if camera opened successfully
            if not cap.isOpened():
                cap.release()
                time.sleep(2)  # Wait longer between attempts
        except Exception as e:
            print(f"Camera initialization attempt {attempts} failed: {str(e)}")
            time.sleep(2)
    
    if cap is None or not cap.isOpened():
        return "Failed to access camera after multiple attempts. Please try again later.", 500

    captured_images = 0  # Counter for saved images

    try:
        while captured_images < 25:  # Keep capturing until 25 images are saved
            ret, frame = cap.read()
            if not ret or frame is None:
                print("Failed to capture frame")
                time.sleep(0.5)
                continue  # Skip if frame is not captured properly
            
            faces = extract_faces(frame)
            if len(faces) > 0:
                for (x, y, w, h) in faces:
                    cv2.rectangle(frame, (x, y), (x + w, y + h), (255, 0, 20), 2)
                    cv2.putText(frame, f'Face Detected - Saving Image {captured_images + 1}/25', (30, 30),
                                cv2.FONT_HERSHEY_SIMPLEX, 1, (255, 0, 20), 2, cv2.LINE_AA)
                    
                    name = f'{email}_{captured_images + 1}.jpg'  # Save multiple images
                    cv2.imwrite(f'{userimagefolder}/{name}', frame[y:y + h, x:x + w])  # Save face only
                    
                    captured_images += 1  # Increase count
                    time.sleep(0.2)  # Add small delay between captures
                    
                    if captured_images >= 25:  # Stop after 25 images
                        break

            cv2.imshow('Adding New User', frame)
            if cv2.waitKey(1) == 27:  # Press 'Esc' to exit early
                break
            
            # Break if we've captured enough images
            if captured_images >= 25:
                break
                
    except Exception as e:
        print(f"Error during face capture: {str(e)}")
    finally:
        # Always ensure camera is released
        if cap is not None:
            cap.release()
        cv2.destroyAllWindows()
    
    # Only train if we captured images successfully
    if captured_images > 0:
        try:
            print('Training Model')
            train_model()
            return redirect("http://localhost/EMP_ATTENDANCE/index.php?registration=complete")
        except Exception as e:
            print(f"Error training model: {str(e)}")
            return "Error training the facial recognition model. Please try again."
    else:
        return "Failed to detect any faces. Please try again with better lighting."


#### Our main function which runs the Flask App
app.run(debug=True,port=1000)
if __name__ == '__main__':
    pass
#### This function will run when we add a new user
